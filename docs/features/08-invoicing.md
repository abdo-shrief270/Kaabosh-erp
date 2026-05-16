# Invoicing & Receivables

> Create, send, post, and collect on customer invoices. Includes recurring invoices, payment recording, credit notes, aging reminders, and a collections workflow.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_invoices` — invoice + recurring invoice CRUD, pre-check, cancel, post-to-GL, credit note, PDF.
  - `send_invoices` — send invoice to client.
  - `manage_payments` — payments CRUD.
  - `manage_collections` — write-off, escalate, action log.
  - `manage_settings` — invoice settings, aging reminder settings.
- **Feature flags:** `invoicing`, `collections`.
- **Rate limits:** `throttle:10,1` on payments create.

## Invoices

### `GET /v1/invoices`
**Query:** `client_id, status (draft|sent|partial|paid|overdue|cancelled|credited), from_date, to_date, due_from, due_to, search, min_amount, max_amount, currency, per_page, sort`.

### `POST /v1/invoices`
Create draft invoice.
```json
{
  "client_id": "int (required)",
  "date": "YYYY-MM-DD (required)",
  "due_date": "YYYY-MM-DD (required)",
  "currency": "string (optional, default tenant base)",
  "exchange_rate": "number (optional)",
  "type": "standard | credit | debit (default standard)",
  "reference": "string (optional)",
  "notes": "string (optional)",
  "terms": "string (optional)",
  "project_id": "int (optional)",
  "lines": [
    {
      "description": "string (required)",
      "item_id": "int (optional, inventory product)",
      "quantity": "number (required)",
      "unit_price": "number (required)",
      "discount_percent": "number (optional, 0-100)",
      "vat_rate": "number (optional, default 14)",
      "account_id": "int (optional) — revenue account",
      "cost_center_id": "int (optional)",
      "eta_item_code": "string (optional)"
    }
  ]
}
```

### `POST /v1/invoices/pre-check`
Dry-run validation (credit limit, duplicate check, tax compliance).
```json
{ "client_id": 1, "total": 5000, "date": "2026-04-18" }
```
**Response:** `{ "ok": true, "warnings": ["client near credit limit"] }`

### `GET /v1/invoices/{invoice}`
Full invoice with client, lines, payments, GL posting status, ETA status.

### `PUT /v1/invoices/{invoice}`
Edit (only `draft` status).

### `DELETE /v1/invoices/{invoice}`
Soft-delete drafts.

### `POST /v1/invoices/{invoice}/cancel`
```json
{ "reason": "string" }
```

### `POST /v1/invoices/{invoice}/post-to-gl`
Creates and posts the accounting entry (DR AR, CR Revenue + CR VAT payable).

### `POST /v1/invoices/{invoice}/credit-note`
Issue a credit note against an invoice.
```json
{
  "date": "YYYY-MM-DD",
  "reason": "string",
  "lines": [ /* partial lines to credit */ ],
  "notes": "string (optional)"
}
```

### `GET /v1/invoices/{invoice}/pdf`
Returns a PDF (application/pdf) or JSON link. Supports `?locale=ar|en` and `?template=slug`.

### `POST /v1/invoices/{invoice}/send`
**Permission:** `send_invoices`
Send via email (+ optional WhatsApp).
```json
{
  "to": ["email@..."],
  "cc": ["..."],
  "subject": "string (optional)",
  "message": "string (optional)",
  "channels": ["email","whatsapp"]
}
```

---

## Recurring Invoices

### `GET|POST|GET|PUT|DELETE /v1/recurring-invoices(/{recurringInvoice})`
**Store body:**
```json
{
  "client_id": "int (required)",
  "frequency": "weekly | monthly | quarterly | yearly (required)",
  "start_date": "YYYY-MM-DD",
  "end_date": "YYYY-MM-DD (optional)",
  "day_of_month": 1,
  "auto_send": true,
  "lines": [ /* same shape as invoice lines */ ]
}
```
A scheduled job generates a real invoice each cycle.

---

## Payments

### `GET /v1/payments`
**Query:** `client_id, invoice_id, from_date, to_date, payment_method, per_page`.

### `POST /v1/payments`
**Middleware:** `throttle:10,1`
```json
{
  "invoice_id": "int (required)",
  "amount": "number (required)",
  "payment_date": "YYYY-MM-DD (required)",
  "payment_method": "cash | bank_transfer | cheque | card | paymob | fawry",
  "reference": "string (optional) — bank ref / cheque no",
  "bank_account_id": "int (optional) — destination GL account",
  "notes": "string (optional)"
}
```

### `DELETE /v1/payments/{payment}`
Void a payment. Reverses GL posting.

---

## Collections (`feature:collections`)

### `POST /v1/invoices/{invoice}/write-off`
**Permission:** `manage_collections`
```json
{ "amount": 5000, "account_id": 5710, "reason": "string" }
```

### `POST /v1/invoices/{invoice}/escalate`
Move to legal / collections agency stage.
```json
{ "assigned_to": "user_id (optional)", "notes": "string" }
```

### `GET /v1/collections/overview`
Aggregate: total AR, overdue, aging, top debtors.

### `GET /v1/collections/actions`
List collection activities (email sent, call made).

### `POST /v1/collections/actions`
```json
{
  "action_type": "email | sms | call | visit",
  "invoice_id": "int (optional)",
  "client_id": "int (optional)",
  "outcome": "promised | unreachable | disputed | paid",
  "notes": "string"
}
```

### `GET /v1/collections/clients/{client}`
Client-level collection summary.

### `GET /v1/collections/reports/effectiveness`
Collector performance KPIs.

---

## Invoice Settings (`permission: manage_settings`)

### `GET /v1/invoice-settings`
### `PUT /v1/invoice-settings`
```json
{
  "default_due_days": 30,
  "prefix": "INV-",
  "numbering_strategy": "per_year | continuous",
  "default_currency": "EGP",
  "default_vat_rate": 14,
  "logo_url": "...",
  "terms": "string",
  "footer": "string",
  "default_template": "slug",
  "send_reminder_days_before_due": 3
}
```

---

## Aging Reminders (`permission: manage_settings`)

### `GET|PUT /v1/aging-reminders/settings`
```json
{
  "enabled": true,
  "schedule": [
    { "days_past_due": 7, "channel": "email", "template": "reminder_7" },
    { "days_past_due": 30, "channel": "email", "template": "final_notice" }
  ],
  "cc_internal": ["finance@..."]
}
```

### `GET /v1/aging-reminders/history`
Every reminder dispatched across the tenant.

### `GET /v1/aging-reminders/invoices/{invoiceId}/history`
Reminder history for a single invoice.

### `POST /v1/aging-reminders/trigger`
Manually fire the reminder job (idempotent per day).

## Notes
- Invoice numbering is strictly sequential per the configured strategy; gaps are flagged by the Anomaly Detection module.
- VAT defaults to 14% (Egypt) but is overridable per line.
- Posting to GL is the boundary: once posted, edits are not allowed — use credit note.
- ETA e-invoice submission is a separate step — see `15-eta-einvoicing.md`.
