# Провериум

Провериум — платформа проверки знаний на Laravel. Сервис позволяет собирать тесты, выпускать персональные бумажные бланки, распознавать сканы через OCR и вести журнал оценок по учебным группам.

## Что умеет система

- создавать тесты с несколькими вариантами и шкалой оценивания;
- импортировать и экспортировать тесты в `JSON` и `XLSX`;
- выпускать персональные бланки для группы или выбранных учеников;
- проверять сканы бланков через Paddle OCR;
- разбирать чужие бланки по существующему тесту без привязки к локальному ученику;
- сохранять результаты в журнале группы и выгружать журнал в `XLSX`.

## Стек

- `PHP 8.2` и `Laravel 12`;
- `MySQL` или `SQLite`;
- `Vite` и `Tailwind CSS`;
- Python-модуль `ocr_paddle_module` для OCR;
- Railway + `railpack.json` для production-сборки Python-окружения.

## Быстрый старт

1. Установите PHP-зависимости:

```bash
composer install
```

2. Создайте `.env` на основе `.env.example` и проверьте базовые значения:

```env
APP_NAME=Провериум
APP_SLUG=proverium
```

3. Сгенерируйте ключ и примените миграции:

```bash
php artisan key:generate
php artisan migrate
```

4. Установите фронтенд-зависимости:

```bash
npm install
```

5. Для локальной разработки запустите:

```bash
composer run dev
```

6. Для production-сборки фронтенда:

```bash
npm run build
```

## OCR-модуль

Для локального OCR нужен Python и зависимости из `ocr_paddle_module/requirements.txt`.

Windows PowerShell:

```powershell
python -m venv .venv
.venv\Scripts\python -m pip install --upgrade pip setuptools wheel
.venv\Scripts\python -m pip install -r ocr_paddle_module/requirements.txt
```

Linux/macOS:

```bash
python -m venv .venv
.venv/bin/python -m pip install --upgrade pip setuptools wheel
.venv/bin/python -m pip install -r ocr_paddle_module/requirements.txt
```

В Railway Python-окружение поднимается автоматически через `railpack.json`.
Переменную `PADDLE_OCR_PYTHON` лучше оставить пустой, чтобы приложение брало проектный `.venv/bin/python`.
Если указывать путь вручную, для Linux/Railway используйте `.venv/bin/python`, а не общий `python`.

## Импорт и экспорт тестов

- В `JSON` можно указывать `variant_count` у теста и `variant` у каждого вопроса.
- Если `variant` не задан, вопрос считается частью варианта `1`.
- `XLSX`-импорт ожидает колонки `question_text`, `variant`, `type`, `points`, `answer_a` ... `answer_d`, `correct`.

Пример файла лежит в [examples/import-questions-example.json](examples/import-questions-example.json).

## Полезные команды

```bash
php artisan test
php artisan test --filter=import
php artisan test --filter=foreign_scan_preview
```

## Лицензия

Проект распространяется по лицензии `MIT`.
