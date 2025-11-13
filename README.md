# YoPrint - CSV Product Importer (Laravel)

A small Laravel application that accepts CSV uploads, processes them in the background, and upserts product rows into the database.

Key features:
- Idempotent uploads: files are cleaned (non-UTF-8 characters removed) and stored using a stable hash-based filename so the same file can be uploaded multiple times without creating duplicate `uploads` entries.
- Background processing: each upload schedules a queued job (`ProcessCsvUpload`) that parses the CSV and performs UPSERTs into the `products` table.
- UTF-8 cleaning: all file contents and individual text fields are sanitized to remove problematic characters before parsing or saving.
- UPSERT by `unique_key`: product rows are matched by `unique_key` and updated if present, otherwise created.

Quick setup
1. Install npm / Composer dependencies:

```bash
composer install
npm install
```

2. Configure environment: copy `.env.example` to `.env`, set DB connection and queue driver (e.g. `database` or `redis`).

3. Run migrations:

```bash
php artisan migrate
```

4. Start uploading files:

- @pianburp

