# Documents & Files

> Upload, search, tag, download, archive, and bulk-manage tenant-owned documents (invoices, receipts, contracts, tax filings, working papers) with storage quota enforcement.

## Access & Auth
- **Authentication:** Bearer token via Sanctum (`auth:sanctum`)
- **Tenant scope:** yes — `tenant, active, enforce.2fa, set_timezone, set_locale, meter.usage`
- **Permissions required:** `permission:manage_documents`
- **Feature flags:** `feature:documents`
- **Rate limits:** standard authenticated limits (no custom throttle)

Uploads are stored on the configured tenant storage disk (typically S3 or local). The service deduplicates by SHA-256 hash, enforces a per-tenant quota (`max_bytes`, `max_files`) that is returned by `GET /v1/documents/quota`, and emits activity log entries on every mutation.

**Allowed MIME types for single uploads:** `pdf, doc, docx, xls, xlsx, jpg, jpeg, png, gif, webp, txt, csv, zip, rar`.
**Max file size:** 20 MB (20480 KB) per file.
**Bulk upload limit:** 10 files per request.

**Supported categories (enum `DocumentCategory`):**
`tax_document | invoice | receipt | contract | financial_statement | correspondence | working_paper | other`.

---

## Endpoints

### `GET /v1/documents/quota` — Storage quota snapshot
**Purpose:** Returns the tenant's current document storage usage versus plan limit.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:documents, permission:manage_documents`
**Response 200:**
```json
{
  "data": {
    "max_bytes": 5368709120,
    "used_bytes": 1203456789,
    "max_files": 10000,
    "used_files": 342,
    "usage_percent": 22.42,
    "remaining_bytes": 4165252331,
    "max_bytes_human": "5.00 GB",
    "used_bytes_human": "1.12 GB"
  }
}
```
**Notes:** Call this before large uploads to avoid `413 Payload Too Large` / quota errors.

---

### `POST /v1/documents/bulk` — Bulk upload
**Purpose:** Upload up to 10 documents in a single multipart request.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:documents, permission:manage_documents`
**Request body (multipart/form-data):**
| Field | Type | Notes |
|---|---|---|
| `files[]` | file[] | required, 1..10 files, each ≤ 20 MB |
| `client_id` | int | optional, must belong to tenant |
| `category` | string | optional, one of `DocumentCategory` values |

**Response 201 / 206:**
```json
{
  "data": [ { "id": 101, "name": "invoice-001.pdf", "mime_type": "application/pdf", "size_bytes": 184320, "category": "invoice", "download_url": "https://.../v1/documents/101/download" } ],
  "errors": [ { "file": "bad.exe", "error": "Unsupported mime type." } ],
  "uploaded": 1,
  "failed": 1
}
```
**Notes:** Returns `201 Created` when all files succeed; `206 Partial Content` when at least one file errors. Errors are per-file and do not roll back successful uploads.

---

### `GET /v1/documents` — List documents
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:documents, permission:manage_documents`
**Query params:**
- `search` — matches `name` / `description`
- `client_id` — filter by associated client
- `category` — one of `DocumentCategory`
- `is_archived` (bool) — default returns both; `true` to only see archived
- `uploaded_by` — user id
- `from`, `to` (`YYYY-MM-DD`) — filter by `created_at`
- `sort_by` (e.g. `created_at`, `size_bytes`, `name`)
- `sort_direction` (`asc | desc`)
- `per_page` (default 15, max 100)

**Response 200:** `DocumentResource` paginated collection (see shape below).

---

### `POST /v1/documents` — Upload a single document
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:documents, permission:manage_documents`
**Request body (multipart/form-data):**
| Field | Type | Notes |
|---|---|---|
| `file` | file | required, ≤ 20 MB, whitelisted MIME |
| `client_id` | int | optional |
| `category` | string | optional, `DocumentCategory` enum value |
| `description` | string | optional, max 1000 chars |
| `metadata` | object | optional, arbitrary JSON key/value |

**Response 201:** A single `DocumentResource`:
```json
{
  "data": {
    "id": 101,
    "name": "invoice-0033.pdf",
    "disk": "s3",
    "mime_type": "application/pdf",
    "size_bytes": 184320,
    "size_human": "180 KB",
    "hash": "sha256:abc123...",
    "category": "invoice",
    "storage_tier": "hot",
    "description": "Client INV-0033",
    "metadata": { "source": "manual" },
    "is_archived": false,
    "archived_at": null,
    "uploaded_by": 7,
    "client_id": 3,
    "download_url": "https://.../v1/documents/101/download",
    "created_at": "2026-04-18T10:14:00Z",
    "updated_at": "2026-04-18T10:14:00Z"
  }
}
```

---

### `GET /v1/documents/{document}` — Show a document
**Response 200:** `DocumentResource` with `client` and `uploaded_by_user` eager-loaded.

---

### `PUT /v1/documents/{document}` — Update metadata
**Request body:**
```json
{
  "name": "optional, max 255",
  "client_id": "optional integer (must belong to tenant)",
  "category": "optional DocumentCategory",
  "description": "optional, max 1000",
  "metadata": { "key": "value" }
}
```
**Response 200:** Updated `DocumentResource`.
**Notes:** Updates metadata only; to replace the underlying file, delete + re-upload.

---

### `DELETE /v1/documents/{document}` — Delete
**Response 200:** `{ "message": "Document deleted successfully." }`
**Notes:** Soft delete with retention window before the file is purged from storage (configurable per plan).

---

### `GET /v1/documents/{document}/download` — Download
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:documents, permission:manage_documents`
**Response 200:** Streamed file with the original `Content-Type` and `Content-Disposition: attachment; filename="<original-name>"`.
**Notes:** Route name: `documents.download`. Generates a signed URL internally; safe for server-side redirects.

---

### `POST /v1/documents/{document}/archive` — Archive
**Purpose:** Marks the document as archived (`is_archived=true`, sets `archived_at`); keeps it accessible via search with `is_archived=true` but hides from default listings.
**Response 200:** Updated `DocumentResource`.

---

### `POST /v1/documents/{document}/unarchive` — Unarchive
**Response 200:** Updated `DocumentResource` with `is_archived=false`, `archived_at=null`.

---

## Related features
- **Client Portal** (`/v1/portal/documents/*`) — clients upload/download their own subset of documents via the portal.
- **Invoicing** — invoice PDFs are auto-generated and can be stored as documents (category `invoice`).
- **ETA E-Invoicing** — submission receipts may be captured as documents.
- **Working Papers** (`/v1/working-papers/*`) — engagement-linked documents use the same storage backend but have a dedicated model.
