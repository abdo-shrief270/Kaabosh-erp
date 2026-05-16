# Data Import

> Generic import pipeline for clients, vendors, products, accounts, invoices, bills, opening balances, and journal entries. Accepts CSV / XLSX, validates with a dry-run, and queues a background import job with per-row error reporting.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** per-type (`manage_clients`, `manage_accounts`, `manage_journal_entries`, `manage_inventory`, etc.). Generic routes under `/v1/import` require `manage_clients` as the minimum baseline.
- **Feature flag:** per-type (e.g. `clients`, `accounting`, `invoicing`).
- **Rate limits:** none explicit (jobs enforce concurrency limits).

## Generic Import

### `GET /v1/import`
List import jobs.
**Query:** `type, status (queued|running|completed|failed), per_page`.

### `POST /v1/import`
Multipart.
```json
{
  "file": "file (required, csv/xlsx)",
  "import_type": "clients | vendors | products | accounts | invoices | bills | opening_balances | journal_entries (required)",
  "match_strategy": "skip | update | upsert (default skip)",
  "dry_run": "bool (optional, default false) — validate only",
  "mapping": { "CSV Column": "model_field" },
  "options": { /* per-type options */ }
}
```
Returns an `ImportJobResource` — track via `GET /v1/import/{importJob}`.

### `GET /v1/import/template/{type}`
Download a CSV template for a given import type with the expected columns and sample rows.

### `GET /v1/import/{importJob}`
Progress + error report.
```json
{
  "data": {
    "id": 42,
    "type": "invoices",
    "status": "completed | running | failed",
    "total_rows": 500,
    "processed": 500,
    "imported_count": 497,
    "error_count": 3,
    "errors": [
      { "row": 17, "field": "client_id", "message": "client not found" }
    ]
  }
}
```

---

## Type-Specific Shortcuts

### `POST /v1/import/clients`
Shortcut for `POST /v1/import` with `import_type=clients`.
**Permission:** `manage_clients`.

### `POST /v1/import/accounts`
`import_type=accounts`. **Permission:** `manage_accounts`.

### `POST /v1/import/opening-balances`
`import_type=opening_balances`. **Permission:** `manage_journal_entries`. Creates an opening-balance JE for the selected fiscal year.

## Notes
- The importer is idempotent when `match_strategy=upsert` is used with a unique key (e.g. `tax_id` for clients).
- Failed rows are returned in the error report — the successfully processed rows are committed unless the whole import is atomic (configurable per type via `options.atomic`).
- CSV templates include localized headers (Arabic / English) and are the authoritative source of expected columns.
