# Accounts Payable — Vendors, Bills & Bill Payments

> Manage vendors, record bills (supplier invoices), approve them, and make payments. Includes vendor statements and aging.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_vendors` — vendor CRUD, statements, aging.
  - `manage_bills` — bills CRUD, approval, cancel, bill payments.
- **Feature flag:** `bills_vendors`.
- **Rate limits:** none explicit.

## Vendors

### `GET /v1/vendors`
**Query:** `search, status, city, country, per_page, sort`.

### `POST /v1/vendors`
```json
{
  "name": "string (required)",
  "email": "string (optional)",
  "phone": "string (optional)",
  "tax_id": "string (optional) — ETA tax registration",
  "commercial_registration": "string (optional)",
  "address": "string (optional)",
  "city": "string (optional)",
  "country": "string (optional, default EG)",
  "default_account_id": "int (optional) — default expense account",
  "payment_terms": "string (optional) — e.g. Net 30",
  "currency": "string (optional, default EGP)",
  "contact_person": "string (optional)",
  "notes": "string (optional)"
}
```

### `GET /v1/vendors/{vendor}`
```json
{
  "data": {
    "id": 1, "name": "...", "tax_id": "...",
    "balance": -23500, "currency": "EGP",
    "open_bills_count": 4,
    "aging_buckets": { "0_30": 10000, "31_60": 13500, "61_90": 0, "90_plus": 0 },
    "last_payment_at": "2026-03-18"
  }
}
```

### `PUT /v1/vendors/{vendor}` / `DELETE /v1/vendors/{vendor}`

### `GET /v1/vendors/{vendor}/statement`
Vendor statement for a period.
**Query:** `from_date, to_date, format (json|pdf|csv)`.

### `GET /v1/vendors/reports/aging`
Tenant-wide AP aging report.

---

## Bills

### `GET /v1/bills`
**Query:** `vendor_id, status (draft|pending_approval|approved|paid|partial|cancelled), from_date, to_date, due_from, due_to, search, per_page`.

### `POST /v1/bills`
```json
{
  "vendor_id": "int (required)",
  "bill_number": "string (required) — vendor's invoice number",
  "bill_date": "YYYY-MM-DD (required)",
  "due_date": "YYYY-MM-DD (required)",
  "currency": "string (optional, default EGP)",
  "exchange_rate": "number (optional)",
  "reference": "string (optional)",
  "notes": "string (optional)",
  "attachments": ["document_id (optional)"],
  "lines": [
    {
      "description": "string (required)",
      "quantity": "number (required)",
      "unit_price": "number (required)",
      "account_id": "int (required) — expense/asset account",
      "cost_center_id": "int (optional)",
      "project_id": "int (optional)",
      "vat_rate": "number (optional, default 14)",
      "wht_rate": "number (optional) — withholding tax rate",
      "eta_item_code": "string (optional)"
    }
  ]
}
```

### `GET /v1/bills/{bill}`
Detail with lines, payments, approval history.

### `PUT /v1/bills/{bill}`
Update draft.

### `DELETE /v1/bills/{bill}`
Soft-delete draft.

### `POST /v1/bills/{bill}/approve`
Moves `pending_approval` → `approved`. Posts to GL (DR Expense + DR VAT receivable, CR AP + CR WHT payable if applicable).
```json
{ "notes": "string (optional)" }
```

### `POST /v1/bills/{bill}/cancel`
```json
{ "reason": "string" }
```

---

## Bill Payments

### `GET /v1/bills/{bill}/payments`
List payments made against a bill.

### `POST /v1/bills/{bill}/payments`
```json
{
  "amount": "number (required)",
  "payment_date": "YYYY-MM-DD (required)",
  "payment_method": "cash | bank_transfer | cheque | card",
  "bank_account_id": "int (optional) — source GL account",
  "reference": "string (optional)",
  "wht_amount": "number (optional) — withheld at source",
  "notes": "string (optional)"
}
```

### `DELETE /v1/bill-payments/{billPayment}/void`
Void a bill payment. Reverses GL posting.

## Notes
- Bills may trigger WHT obligations — certificates are generated via `/v1/wht-certificates/generate` (see `14-tax-management.md`).
- The `bills` feature flag must be enabled for the tenant plan.
- Approval can also be orchestrated via the Approval Workflow engine (see `20-approvals-alerts.md`) for multi-step approvals with conditions.
