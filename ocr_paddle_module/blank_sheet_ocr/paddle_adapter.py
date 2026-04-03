from __future__ import annotations

import os
from dataclasses import dataclass
from typing import Any

os.environ.setdefault("PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK", "True")

try:
    from paddleocr import PaddleOCR
except Exception:  # pragma: no cover - optional runtime dependency
    PaddleOCR = None


@dataclass
class PaddleStatus:
    available: bool
    details: dict[str, Any]


def runtime_status() -> PaddleStatus:
    return PaddleStatus(
        available=PaddleOCR is not None,
        details={
            "engine": "paddleocr",
            "available": PaddleOCR is not None,
        },
    )
