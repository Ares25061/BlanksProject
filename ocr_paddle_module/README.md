# Blank Sheet OCR Module

Отдельный Python-модуль для Laravel-проекта.

Назначение:

- выровнять скан по угловым меткам;
- найти клетки по манифесту страницы;
- посчитать заполненность каждой клетки;
- считать клетку выбранной, если заполненность `>= 0.40`.

Текстовые ответы модуль не распознаёт. PaddleOCR оставлен как отдельный runtime внутри модуля для дальнейшего расширения и диагностического OCR, но текущий рабочий поток проверяет только клетки.

## Установка

```bash
pip install -r ocr_paddle_module/requirements.txt
```

## Вызов

Laravel вызывает модуль так:

```bash
python ocr_paddle_module/blank_sheet_ocr/cli.py --request /path/to/request.json
```

Формат `request.json`:

```json
{
  "image_path": "C:/path/to/image.jpg",
  "fill_threshold": 0.4,
  "manifest": {
    "page_width_mm": 210,
    "page_height_mm": 297,
    "marker_centers_mm": {},
    "question_range": {"start": 1, "end": 10},
    "questions": []
  }
}
```
