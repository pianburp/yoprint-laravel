**YoPrint - CSV Product Importer (Laravel)**

A small Laravel application that accepts CSV uploads, processes them in the background, and upserts product rows into the database.

Key behaviours:
- Idempotent uploads: files are cleaned (non-UTF-8 characters removed) and stored using a stable hash-based filename so the same file can be uploaded multiple times without creating duplicate `uploads` entries.
- Background processing: each upload schedules a queued job (`ProcessCsvUpload`) that parses the CSV and performs UPSERTs into the `products` table.
- UTF-8 cleaning: all file contents and individual text fields are sanitized to remove problematic characters before parsing or saving.
- UPSERT by `unique_key`: product rows are matched by `unique_key` and updated if present, otherwise created.

Quick setup
1. Install PHP / Composer dependencies:

```bash
composer install
```

2. Configure environment: copy `.env.example` to `.env`, set DB connection and queue driver (e.g. `database` or `redis`).

3. Run migrations:

```bash
php artisan migrate
```

4. Start a queue worker to process uploaded files:

```bash
php artisan queue:work --tries=3
```

Uploading and testing
- Use the application's upload endpoint (controller `UploadController`) to POST a CSV file under the `file` field.
- The controller cleans and stores the file, records an `uploads` entry (status `pending`) and dispatches `ProcessCsvUpload`.
- The worker will process the file, perform per-row `updateOrCreate` on `products`, and update the upload `status` to `processing`, `completed`, or `failed`.

Notes & recommendations
- For large CSVs consider streaming parsing and committing in chunks instead of a single large DB transaction.
- Normalize CSV headers (case and whitespace) if input files vary; the current job expects exact header names such as `UNIQUE_KEY` and `PIECE_PRICE`.
- Ensure a queue worker is running in your environment; otherwise jobs will remain queued and uploads will not be processed.

If you'd like, I can add a short example `curl` command to upload test CSVs, add header normalization in the job, or create a migration to store the original filename separately.

