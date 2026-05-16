# ETA E-Invoicing (Egyptian Tax Authority)

> Real-time electronic invoice submission to the Egyptian Tax Authority per the mandatory e-invoicing regime. Covers settings, document preparation and submission, item code (GS1/EGS) mapping, compliance dashboard, and bulk operations.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_eta` on every endpoint.
- **Feature flag:** `e_invoice`.
- **Rate limits:** none explicit (ETA API throttling is handled upstream).

## ETA Settings

### `GET /v1/eta/settings`
Current ETA configuration for the tenant.
```json
{
  "data": {
    "tax_number": "...",
    "legal_name": "...",
    "activity_type": "...",
    "branch_id": "...",
    "environment": "preprod | prod",
    "client_id_configured": true,
    "certificate_installed": true,
    "last_token_refresh": "...",
    "auto_submit": false
  }
}
```

### `PUT /v1/eta/settings`
```json
{
  "tax_number": "string (required) — 9-digit ETA tax registration",
  "legal_name": "string (required)",
  "activity_type": "string (required)",
  "branch_id": "string (required) — 0 for HQ",
  "environment": "preprod | prod",
  "client_id": "string (required) — ETA API client id",
  "client_secret": "string (required, write-only)",
  "certificate": "file | pem string — ETA signing cert",
  "auto_submit": "bool (default false) — auto-submit on invoice post"
}
```

---

## ETA Documents

### `GET /v1/eta/documents`
List e-invoice documents.
**Query:** `status (prepared|submitted|valid|rejected|cancelled), from_date, to_date, invoice_id, per_page`.

### `POST /v1/eta/documents/{invoice}/prepare`
Build the ETA-compliant JSON payload from an invoice. Validates item codes, tax rates, activity codes. Does NOT transmit.
**Response:** the signed ETA document JSON and validation warnings/errors.

### `POST /v1/eta/documents/{invoice}/submit`
Submit the prepared document to the ETA.
**Response:**
```json
{
  "data": {
    "status": "submitted | valid | rejected",
    "submission_uuid": "...",
    "government_timestamp": "...",
    "response_code": 200,
    "errors": []
  }
}
```

### `GET /v1/eta/documents/{invoice}`
Status + last response for the invoice's ETA document.

### `POST /v1/eta/documents/{invoice}/cancel`
Cancel a submitted e-invoice at ETA.
```json
{ "reason": "string (required)" }
```

### `POST /v1/eta/documents/{invoice}/check-status`
Re-poll ETA for the latest document status.

### `POST /v1/eta/reconcile`
Reconcile local state vs ETA-reported state for a period.
```json
{ "from_date": "YYYY-MM-DD", "to_date": "YYYY-MM-DD" }
```

---

## Bulk Operations

### `GET /v1/eta/compliance-dashboard`
Submission health: success rate, rejected count, unmapped item lines, certificate expiry.

### `POST /v1/eta/bulk-retry`
Retry all rejected submissions.
```json
{ "from_date": "YYYY-MM-DD (optional)", "to_date": "YYYY-MM-DD (optional)" }
```

### `POST /v1/eta/bulk-status-check`
Re-poll status for submitted-but-unconfirmed documents.

---

## ETA Item Codes (GS1 / EGS mapping)

### `GET /v1/eta/item-codes`
List uploaded item codes.
**Query:** `code_type (GS1|EGS), search, per_page`.

### `POST /v1/eta/item-codes`
```json
{
  "code": "string (required) — e.g. GS1 GTIN",
  "code_type": "GS1 | EGS (required)",
  "description": "string (required)",
  "parent_code": "string (optional)"
}
```

### `GET|PUT|DELETE /v1/eta/item-codes/{itemCode}`

### `POST /v1/eta/item-codes/bulk-assign`
Assign an item code to many products or GL accounts at once.
```json
{
  "item_code_id": "int (required)",
  "target_type": "product | account (required)",
  "target_ids": "int[] (required)"
}
```

### `POST /v1/eta/item-codes/bulk-import`
Import item codes from CSV (`code, description, parent_code`).
**Request:** multipart `file`.

### `POST /v1/eta/item-codes/auto-assign`
Run heuristic/ML auto-assignment of codes to unmapped products.

### `GET /v1/eta/item-codes/usage-report`
Most-used item codes, unused codes, orphans.

### `GET /v1/eta/item-codes/unmapped-lines`
Invoice lines lacking an ETA code — the main blocker for compliance.
**Query:** `invoice_id, from_date, to_date`.

### `GET /v1/eta/item-codes/suggest`
```
?description=cement+50kg+bag
```
Returns ranked code suggestions.

### `GET /v1/eta/item-codes/mappings`
All product/account → code mappings.

### `POST /v1/eta/item-codes/mappings`
```json
{
  "target_type": "product | account",
  "target_id": "int",
  "item_code_id": "int"
}
```

### `DELETE /v1/eta/item-codes/mappings/{mapping}`

## Notes
- Certificates and signing keys are stored encrypted. The controller never returns `client_secret` or raw keys.
- The ETA API is mandatory for B2B invoicing in Egypt as of the phase 1–4 rollout. Missing or invalid item codes are the #1 cause of rejection — use the compliance dashboard to drive the unmapped count to zero.
- When `auto_submit=true` is set, invoices are submitted within seconds of posting to GL (async job).
