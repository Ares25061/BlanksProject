from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path
from typing import Any

import cv2
import numpy as np
import pypdfium2 as pdfium

from paddle_adapter import runtime_status

TARGET_WIDTH_PX = 2480
TARGET_HEIGHT_PX = 3508
PAGE_WIDTH_MM = 210.0
PAGE_HEIGHT_MM = 297.0

DEFAULT_MARKER_CENTERS_MM = {
    "top_left": {"x_mm": 9.5, "y_mm": 9.5},
    "top_right": {"x_mm": 200.5, "y_mm": 9.5},
    "bottom_left": {"x_mm": 9.5, "y_mm": 287.5},
    "bottom_right": {"x_mm": 200.5, "y_mm": 287.5},
}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Recognize marked cells on a unified blank sheet.")
    parser.add_argument("--request", required=True, help="Path to JSON request payload.")
    return parser.parse_args()


def load_request(path: str | Path) -> dict[str, Any]:
    return json.loads(Path(path).read_text(encoding="utf-8-sig"))


def normalize_image(image: np.ndarray) -> np.ndarray:
    lab = cv2.cvtColor(image, cv2.COLOR_BGR2LAB)
    l_channel, a_channel, b_channel = cv2.split(lab)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    l_channel = clahe.apply(l_channel)
    merged = cv2.merge((l_channel, a_channel, b_channel))
    return cv2.cvtColor(merged, cv2.COLOR_LAB2BGR)


def marker_template(size: int) -> np.ndarray:
    template = np.full((size, size), 255, dtype=np.uint8)
    line = max(2, size // 7)
    cv2.line(template, (line, size // 2), (size - line - 1, size // 2), 0, line)
    cv2.line(template, (size // 2, line), (size // 2, size - line - 1), 0, line)
    return template


def detect_markers(image: np.ndarray) -> dict[str, tuple[float, float]]:
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    _, binary = cv2.threshold(gray, 120, 255, cv2.THRESH_BINARY_INV)
    height, width = binary.shape[:2]
    quadrants = {
        "top_left": (slice(0, width // 4), slice(0, height // 4)),
        "top_right": (slice(width * 3 // 4, width), slice(0, height // 4)),
        "bottom_left": (slice(0, width // 4), slice(height * 3 // 4, height)),
        "bottom_right": (slice(width * 3 // 4, width), slice(height * 3 // 4, height)),
    }
    markers: dict[str, tuple[float, float]] = {}
    expected_size = max(18, int(min(width, height) * (7.0 / 210.0)))

    for name, (x_slice, y_slice) in quadrants.items():
        roi = binary[y_slice, x_slice]
        best_score = -1.0
        best_center: tuple[float, float] | None = None

        for scale in (0.75, 0.9, 1.0, 1.15, 1.3):
            size = max(18, int(expected_size * scale))
            if roi.shape[0] < size or roi.shape[1] < size:
                continue
            template = marker_template(size)
            result = cv2.matchTemplate(roi, template, cv2.TM_CCOEFF_NORMED)
            _, max_value, _, max_location = cv2.minMaxLoc(result)
            if max_value > best_score:
                best_score = max_value
                best_center = (
                    x_slice.start + max_location[0] + size / 2,
                    y_slice.start + max_location[1] + size / 2,
                )

        if best_center is not None and best_score >= 0.40:
            markers[name] = best_center

    return markers


def destination_points(marker_centers_mm: dict[str, dict[str, float]], page_width_mm: float, page_height_mm: float) -> np.ndarray:
    ordered = []
    for name in ("top_left", "top_right", "bottom_left", "bottom_right"):
        point = marker_centers_mm.get(name, {})
        ordered.append([
            float(point.get("x_mm", 0.0)) * TARGET_WIDTH_PX / page_width_mm,
            float(point.get("y_mm", 0.0)) * TARGET_HEIGHT_PX / page_height_mm,
        ])
    return np.array(ordered, dtype=np.float32)


def align_page(
    image: np.ndarray,
    marker_centers_mm: dict[str, dict[str, float]] | None = None,
    page_width_mm: float = PAGE_WIDTH_MM,
    page_height_mm: float = PAGE_HEIGHT_MM,
) -> tuple[np.ndarray, dict[str, Any]]:
    normalized = normalize_image(image)
    markers = detect_markers(normalized)
    debug: dict[str, Any] = {"markers": {key: [value[0], value[1]] for key, value in markers.items()}, "used_fallback": False}

    if len(markers) == 4:
        src = np.array(
            [
                markers["top_left"],
                markers["top_right"],
                markers["bottom_left"],
                markers["bottom_right"],
            ],
            dtype=np.float32,
        )
        dst = destination_points(marker_centers_mm or DEFAULT_MARKER_CENTERS_MM, page_width_mm, page_height_mm)
        matrix = cv2.getPerspectiveTransform(src, dst)
        aligned = cv2.warpPerspective(normalized, matrix, (TARGET_WIDTH_PX, TARGET_HEIGHT_PX))
        debug["transform"] = matrix.tolist()
        return aligned, debug

    debug["used_fallback"] = True
    resized = cv2.resize(normalized, (TARGET_WIDTH_PX, TARGET_HEIGHT_PX))
    return resized, debug


def try_parse_qr_text(text: str) -> dict[str, Any] | None:
    if not text:
        return None
    try:
        payload = json.loads(text)
    except Exception:
        return None
    return payload if isinstance(payload, dict) else None


def candidate_qr_images(image: np.ndarray) -> list[np.ndarray]:
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY) if image.ndim == 3 else image
    _, binary = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    top_right = image[: max(1, image.shape[0] // 3), image.shape[1] * 2 // 3 :]

    variants = [
        image,
        cv2.cvtColor(gray, cv2.COLOR_GRAY2BGR),
        cv2.cvtColor(binary, cv2.COLOR_GRAY2BGR),
        cv2.resize(image, None, fx=1.75, fy=1.75, interpolation=cv2.INTER_CUBIC),
        cv2.resize(cv2.cvtColor(binary, cv2.COLOR_GRAY2BGR), None, fx=2.0, fy=2.0, interpolation=cv2.INTER_NEAREST),
        top_right,
        cv2.resize(top_right, None, fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC),
    ]

    return [variant for variant in variants if variant.size]


def decode_qr_payload(image: np.ndarray) -> dict[str, Any] | None:
    detector = cv2.QRCodeDetector()

    for candidate in candidate_qr_images(image):
        decoded_text, _, _ = detector.detectAndDecode(candidate)
        parsed = try_parse_qr_text(decoded_text)
        if parsed:
            return parsed

        multi_ok, decoded_list, _, _ = detector.detectAndDecodeMulti(candidate)
        if multi_ok:
            for text in decoded_list:
                parsed = try_parse_qr_text(text)
                if parsed:
                    return parsed

    return None


def mm_rect_to_pixels(rect: dict[str, Any], image: np.ndarray, page_width_mm: float, page_height_mm: float) -> tuple[int, int, int, int]:
    scale_x = image.shape[1] / max(page_width_mm, 1.0)
    scale_y = image.shape[0] / max(page_height_mm, 1.0)
    x = int(float(rect.get("left_mm", 0.0)) * scale_x)
    y = int(float(rect.get("top_mm", 0.0)) * scale_y)
    width = int(float(rect.get("width_mm", 0.0)) * scale_x)
    height = int(float(rect.get("height_mm", 0.0)) * scale_y)
    return x, y, max(1, width), max(1, height)


def extract_rect(image: np.ndarray, rect: dict[str, Any], page_width_mm: float, page_height_mm: float) -> np.ndarray:
    x, y, width, height = mm_rect_to_pixels(rect, image, page_width_mm, page_height_mm)
    x0 = max(0, x)
    y0 = max(0, y)
    x1 = min(image.shape[1], x + width)
    y1 = min(image.shape[0], y + height)
    return image[y0:y1, x0:x1]


def fill_ratio(crop: np.ndarray) -> float:
    if crop.size == 0:
        return 0.0
    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY) if crop.ndim == 3 else crop
    blur = cv2.GaussianBlur(gray, (3, 3), 0)
    _, binary = cv2.threshold(blur, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
    margin = max(2, int(min(binary.shape[:2]) * 0.18))

    if binary.shape[0] > margin * 2 and binary.shape[1] > margin * 2:
        binary = binary[margin:-margin, margin:-margin]

    return float(np.count_nonzero(binary)) / max(binary.size, 1)


def recognize_questions(image: np.ndarray, manifest: dict[str, Any], threshold: float) -> dict[str, Any]:
    page_width_mm = float(manifest.get("page_width_mm", PAGE_WIDTH_MM))
    page_height_mm = float(manifest.get("page_height_mm", PAGE_HEIGHT_MM))
    question_results: list[dict[str, Any]] = []

    for question in manifest.get("questions", []):
        selected_answer_ids: list[int] = []
        selected_letters: list[str] = []
        cells_payload: list[dict[str, Any]] = []

        for cell in question.get("cells", []):
            rect = {
                "left_mm": cell.get("left_mm", 0.0),
                "top_mm": cell.get("top_mm", 0.0),
                "width_mm": cell.get("width_mm", 0.0),
                "height_mm": cell.get("height_mm", 0.0),
            }
            crop = extract_rect(image, rect, page_width_mm, page_height_mm)
            ratio = fill_ratio(crop)
            selected = ratio >= threshold
            answer_id = int(cell.get("answer_id", 0))
            option_letter = str(cell.get("option_letter", "")).strip()

            if selected and answer_id > 0:
                selected_answer_ids.append(answer_id)
                if option_letter:
                    selected_letters.append(option_letter)

            cells_payload.append(
                {
                    "answer_id": answer_id,
                    "option_letter": option_letter,
                    "fill_ratio": round(float(ratio), 4),
                    "selected": selected,
                }
            )

        question_results.append(
            {
                "question_id": int(question.get("question_id", 0)),
                "question_number": int(question.get("question_number", 0)),
                "type": str(question.get("type", "single")),
                "selected_answer_ids": selected_answer_ids,
                "selected_letters": selected_letters,
                "cells": cells_payload,
            }
        )

    return {
        "question_results": question_results,
        "question_range": manifest.get("question_range"),
        "warnings": [],
    }


def handle_identify(image: np.ndarray) -> dict[str, Any]:
    aligned, alignment_debug = align_page(image)
    qr_payload = decode_qr_payload(image) or decode_qr_payload(aligned)
    warnings: list[str] = []

    if qr_payload is None:
        warnings.append("QR code not detected")

    return {
        "qr_payload": qr_payload,
        "warnings": warnings,
        "alignment": alignment_debug,
    }


def handle_recognize(image: np.ndarray, request: dict[str, Any]) -> dict[str, Any]:
    manifest = request["manifest"]
    threshold = float(request.get("fill_threshold", 0.40))
    aligned, alignment_debug = align_page(
        image,
        manifest.get("marker_centers_mm") or DEFAULT_MARKER_CENTERS_MM,
        float(manifest.get("page_width_mm", PAGE_WIDTH_MM)),
        float(manifest.get("page_height_mm", PAGE_HEIGHT_MM)),
    )
    payload = recognize_questions(aligned, manifest, threshold)
    payload["alignment"] = alignment_debug
    payload["qr_payload"] = decode_qr_payload(image) or decode_qr_payload(aligned)
    return payload


def render_pdf_first_page(pdf_path: Path, output_path: Path) -> dict[str, Any]:
    document = pdfium.PdfDocument(str(pdf_path))
    if len(document) == 0:
        raise ValueError(f"PDF has no pages: {pdf_path}")

    bitmap = document[0].render(scale=2.8)
    pil_image = bitmap.to_pil()
    output_path.parent.mkdir(parents=True, exist_ok=True)
    pil_image.save(output_path)

    return {
        "output_path": str(output_path),
    }


def main() -> int:
    args = parse_args()
    request = load_request(args.request)
    operation = str(request.get("operation", "recognize")).strip().lower()
    image_path = Path(request["image_path"])

    if operation == "render_pdf_first_page":
        output_path = Path(request["output_path"])
        payload = render_pdf_first_page(image_path, output_path)
    else:
        image = cv2.imread(str(image_path))
        if image is None:
            print(json.dumps({"error": f"Unable to read image: {image_path}"}, ensure_ascii=False))
            return 1

        if operation == "identify":
            payload = handle_identify(image)
        elif operation == "recognize":
            payload = handle_recognize(image, request)
        else:
            print(json.dumps({"error": f"Unsupported operation: {operation}"}, ensure_ascii=False))
            return 1

    paddle = runtime_status()
    payload["paddle"] = {
        "available": paddle.available,
        "details": paddle.details,
    }
    print(json.dumps(payload, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    sys.exit(main())
