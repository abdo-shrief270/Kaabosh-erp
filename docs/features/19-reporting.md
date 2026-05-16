# Reporting & Analytics

> Financial statements, tax reports, executive KPI dashboards, custom report builder, scheduled email deliveries, streaming CSV/Excel exports, and anomaly detection for the tenant's books.

## Access & Auth
- **Authentication:** Bearer token via Sanctum (`auth:sanctum`)
- **Tenant scope:** yes — all routes live under the `tenant, active, enforce.2fa, set_timezone, set_locale, meter.usage` group
- **Permissions required:**
  - `permission:view_reports` — read access (trial balance, financials, tax reports, PDFs, dashboards, custom reports, anomalies, exports)
  - `permission:manage_reports` — manage scheduled reports
- **Feature flags:**
  - `feature:reports` — executive dashboard, scheduled reports, anomalies
  - `feature:custom_reports` — custom report builder endpoints
- **Rate limits:**
  - `throttle:reports` — all `/v1/reports/*` endpoints
  - `throttle:exports` — all `/v1/export/*` endpoints

All money figures are returned as strings in the tenant base currency (typically `EGP`) unless a `?currency=USD|EUR|...` query override is provided (converts via `ReportCurrencyConverter`). Dates follow ISO-8601 (`YYYY-MM-DD`).

---

## Endpoints

### `GET /v1/reports/trial-balance` — Trial balance
**Purpose:** Opening / debit / credit / closing per account for the period.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `from (YYYY-MM-DD)`, `to (YYYY-MM-DD)`, `currency (ISO 4217, optional)`
**Response 200:**
```json
{
  "from": "2026-01-01",
  "to": "2026-03-31",
  "currency": "EGP",
  "accounts": [
    { "code": "1000", "name": "Cash", "opening": "0.00", "debit": "150000.00", "credit": "40000.00", "closing": "110000.00" }
  ],
  "totals": { "debit": "250000.00", "credit": "250000.00" }
}
```
**Notes:** Read-only; no side effects. Use for period-close verification.

---

### `GET /v1/reports/accounts/{account}/ledger` — Account ledger
**Purpose:** All posted journal lines hitting a given account in the period.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Path params:** `account (int)` — account id scoped to the tenant
**Query params:** `from`, `to`
**Response 200:**
```json
{
  "account": { "id": 12, "code": "1100", "name": "Accounts Receivable" },
  "opening_balance": "0.00",
  "lines": [
    { "date": "2026-02-04", "entry_number": "JE-00041", "description": "Invoice INV-2026-0033", "debit": "11400.00", "credit": "0.00", "running_balance": "11400.00" }
  ],
  "closing_balance": "11400.00"
}
```

---

### `GET /v1/reports/clients/{client}/statement` — Client statement
**Purpose:** Per-client AR statement (invoices, payments, running balance).
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Path params:** `client (int)`
**Query params:** `from`, `to`
**Response 200:**
```json
{
  "client": { "id": 3, "name": "شركة النيل للتجارة", "tax_id": "100-200-300" },
  "opening_balance": "0.00",
  "transactions": [
    { "date": "2026-03-01", "type": "invoice", "reference": "INV-2026-0050", "debit": "5700.00", "credit": "0.00", "balance": "5700.00" }
  ],
  "closing_balance": "5700.00"
}
```

---

### `GET /v1/reports/aging` — AR aging buckets
**Purpose:** 0-30, 31-60, 61-90, 90+ day aging across all clients (or one).
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `client_id (int, optional)`
**Response 200:**
```json
{
  "as_of": "2026-04-18",
  "buckets": { "current": "12000.00", "1_30": "3400.00", "31_60": "800.00", "61_90": "0.00", "90_plus": "1200.00" },
  "clients": [ { "client_id": 3, "name": "شركة النيل", "total": "5400.00", "current": "4200.00", "1_30": "1200.00" } ]
}
```

---

### `GET /v1/reports/income-statement` — Income statement (P&L)
**Purpose:** Revenue, expenses, net income for the period.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `from`, `to`, `currency (optional)`
**Response 200:**
```json
{
  "from": "2026-01-01",
  "to": "2026-03-31",
  "currency": "EGP",
  "revenue": { "accounts": [ { "code": "4000", "name": "Sales", "amount": "500000.00" } ], "total": "500000.00" },
  "expenses": { "accounts": [ { "code": "5100", "name": "Salaries", "amount": "120000.00" } ], "total": "350000.00" },
  "net_income": "150000.00",
  "total_revenue": "500000.00",
  "total_expenses": "350000.00"
}
```

---

### `GET /v1/reports/balance-sheet` — Balance sheet
**Purpose:** Assets / liabilities / equity as of a specific date.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `as_of (YYYY-MM-DD)`, `currency (optional)`
**Response 200:**
```json
{
  "as_of": "2026-03-31",
  "currency": "EGP",
  "assets": { "current": [], "non_current": [], "total": "2500000.00" },
  "liabilities": { "current": [], "non_current": [], "total": "900000.00" },
  "equity": { "accounts": [], "net_income": "150000.00", "total": "1600000.00" },
  "total_assets": "2500000.00",
  "total_liabilities": "900000.00",
  "total_equity": "1600000.00",
  "net_income": "150000.00"
}
```

---

### `GET /v1/reports/cash-flow` — Cash flow statement
**Purpose:** Operating / investing / financing cash movements.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `from`, `to`, `currency (optional)`
**Response 200:**
```json
{
  "from": "2026-01-01",
  "to": "2026-03-31",
  "operating": { "items": [], "total": "180000.00" },
  "investing": { "items": [], "total": "-50000.00" },
  "financing": { "items": [], "total": "-20000.00" },
  "net_change": "110000.00",
  "cash_beginning": "400000.00",
  "cash_ending": "510000.00"
}
```

---

### `GET /v1/reports/comparative/income-statement` — Comparative P&L
**Purpose:** Current vs. prior period P&L with variance.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `current_from`, `current_to`, `prior_from`, `prior_to`
**Response 200:**
```json
{
  "current": { "revenue": {}, "expenses": {}, "total_revenue": "500000.00", "total_expenses": "350000.00" },
  "prior":   { "revenue": {}, "expenses": {}, "total_revenue": "420000.00", "total_expenses": "310000.00" },
  "variance": { "amount": "40000.00", "percent": "36.36" }
}
```

---

### `GET /v1/reports/comparative/balance-sheet` — Comparative balance sheet
**Purpose:** Balance sheet at two dates with asset variance.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `current_as_of`, `prior_as_of`
**Response 200:**
```json
{
  "current": { "assets": {}, "liabilities": {}, "equity": {}, "total_assets": "2500000.00" },
  "prior":   { "assets": {}, "liabilities": {}, "equity": {}, "total_assets": "2250000.00" },
  "variance": { "amount": "250000.00", "percent": "11.11" }
}
```

---

### `GET /v1/reports/income-statement/pdf` — P&L as PDF
**Purpose:** Streams a rendered PDF of the income statement (Arabic + English bilingual).
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `from`, `to`, `currency (optional)`
**Response 200:** `Content-Type: application/pdf` — binary stream.

### `GET /v1/reports/balance-sheet/pdf` — Balance sheet PDF
**Query params:** `as_of`, `currency (optional)`
**Response 200:** PDF binary.

### `GET /v1/reports/cash-flow/pdf` — Cash flow PDF
**Query params:** `from`, `to`, `currency (optional)`
**Response 200:** PDF binary.

### `GET /v1/reports/trial-balance/pdf` — Trial balance PDF
**Query params:** `from`, `to`, `currency (optional)`
**Response 200:** PDF binary.

### `GET /v1/reports/vat-return/pdf` — Egyptian VAT return PDF
**Query params:** `from (required)`, `to (required)`
**Response 200:** PDF binary ready to attach to an ETA VAT filing.

### `GET /v1/reports/wht/pdf` — WHT report PDF
**Query params:** `from (required)`, `to (required)`
**Response 200:** PDF binary.

---

### `POST /v1/reports/pdf/async` — Queue large PDF generation
**Purpose:** Dispatch a background `GenerateReportJob` for heavy reports; notifies the user (in-app + email) when the PDF is ready.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Request body:**
```json
{
  "type": "trial_balance | income_statement | balance_sheet | cash_flow | vat_return | wht_report (required)",
  "from": "YYYY-MM-DD (optional)",
  "to":   "YYYY-MM-DD (optional)",
  "date": "YYYY-MM-DD (optional) — for as-of reports"
}
```
**Response 202:**
```json
{ "message": "Report generation queued. You will be notified when ready." }
```
**Notes:** Result is stored and delivered via notification when the job finishes.

---

### `GET /v1/reports/vat-return` — Egyptian VAT return (14%)
**Purpose:** Computes output VAT, input VAT, and net payable for ETA filing.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:reports`
**Query params:** `from (required)`, `to (required)`
**Response 200:**
```json
{
  "period": { "from": "2026-01-01", "to": "2026-03-31" },
  "output_vat": "70000.00",
  "input_vat": "24000.00",
  "net_vat_payable": "46000.00",
  "lines": [ { "invoice_number": "INV-2026-0001", "client": "...", "net": "10000.00", "vat": "1400.00" } ]
}
```

### `GET /v1/reports/wht` — WHT deductions report
**Query params:** `from (required)`, `to (required)`
**Response 200:**
```json
{ "period": {}, "total_wht": "8500.00", "by_vendor": [ { "vendor_id": 5, "name": "...", "wht": "1200.00" } ] }
```

---

## Executive Dashboard

### `GET /v1/dashboard/overview` — Financial overview
**Purpose:** High-level KPIs for the period (revenue, cash, AR/AP totals).
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:reports, permission:view_reports`
**Query params:** `from`, `to`
**Response 200:**
```json
{ "data": { "revenue": "500000.00", "expenses": "350000.00", "cash_balance": "510000.00", "ar_total": "12000.00", "ap_total": "8000.00" } }
```

### `GET /v1/dashboard/revenue` — Revenue analysis
**Query params:** `from`, `to`
**Response 200:** `{ "data": { "monthly": [], "by_client": [], "growth_mom": "..." } }`

### `GET /v1/dashboard/cash-flow` — Cash flow forecast
**Query params:** `from`, `to`
**Response 200:** `{ "data": { "projected": [], "actual": [], "net_position": "..." } }`

### `GET /v1/dashboard/profitability` — Profitability metrics
**Query params:** `from`, `to`
**Response 200:** `{ "data": { "gross_margin_pct": "...", "net_margin_pct": "...", "by_product": [] } }`

### `GET /v1/dashboard/kpis` — KPI dashboard
**Query params:** `from`, `to`
**Response 200:** `{ "data": { "dso": "...", "dpo": "...", "current_ratio": "...", "quick_ratio": "..." } }`

### `GET /v1/dashboard/comparison` — Period comparison
**Query params:** `period_a (YYYY-MM-DD:YYYY-MM-DD)`, `period_b (YYYY-MM-DD:YYYY-MM-DD)`
**Response 200:** `{ "data": { "period_a": {}, "period_b": {}, "variance": {} } }`
**Notes:** Both period strings must match regex `^\d{4}-\d{2}-\d{2}:\d{4}-\d{2}-\d{2}$`.

---

## Custom Report Builder

### `POST /v1/custom-reports/execute` — Execute inline ad-hoc report
**Purpose:** Build + run a custom account report without persisting it.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:custom_reports, permission:view_reports`
**Request body:**
```json
{
  "accounts": {
    "types": ["asset","liability","equity","revenue","expense"],
    "codes_from": "1000",
    "codes_to": "1999",
    "ids": [1, 2, 3]
  },
  "date_range": { "from": "2026-01-01", "to": "2026-03-31" },
  "columns": ["code","name","opening_balance","debit","credit","closing_balance","net_change","type"],
  "grouping": "flat | parent | type (optional)",
  "include_zero_balances": false,
  "comparison": { "enabled": true, "prior_from": "2025-01-01", "prior_to": "2025-03-31" }
}
```
**Response 200:** `{ "data": { "columns": [], "rows": [], "totals": {}, "comparison": {} } }`

### `GET /v1/custom-reports` — List saved report templates
**Query params:** `per_page (default 15, max 100)`
**Response 200:** Paginated list (own + shared templates for the current user).

### `POST /v1/custom-reports` — Save a template
**Request body:**
```json
{
  "name": "Q1 Asset Summary (required, max 255)",
  "name_ar": "ملخص الأصول (optional)",
  "description": "optional (max 1000)",
  "config": { "accounts": { "types": ["asset"] }, "date_range": {"from":"2026-01-01","to":"2026-03-31"} },
  "is_shared": false
}
```
**Response 201:** `{ "data": {}, "message": "Report template saved." }`

### `GET /v1/custom-reports/{savedReport}` — Show template
**Response 200:** `{ "data": { "id": 1, "name": "...", "config": {}, "creator": {"id":1,"name":"..."} } }`

### `PUT /v1/custom-reports/{savedReport}` — Update template
**Request body:** subset of `name, name_ar, description, config, is_shared`
**Response 200:** `{ "data": {} }`

### `DELETE /v1/custom-reports/{savedReport}` — Delete template
**Response 200:** `{ "message": "Report template deleted." }`

### `GET /v1/custom-reports/{savedReport}/run` — Run a saved template
**Query params:** `from`, `to`, `prior_from`, `prior_to`, `currency (3-letter)`
**Response 200:** `{ "data": { "report_name": "...", "report_name_ar": "...", "rows": [], "totals": {} } }`
**Notes:** Query params override the persisted `config.date_range` / `config.comparison` for this run only.

---

## Scheduled Reports

### `GET /v1/scheduled-reports` — List schedules
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:reports, permission:manage_reports`
**Query params:** `is_active (bool)`, `report_type`, `schedule_type`, `per_page (max 100)`
**Response 200:** Paginated `ScheduledReport[]`.

### `POST /v1/scheduled-reports` — Create schedule
**Request body:**
```json
{
  "report_type": "trial_balance | income_statement | balance_sheet | cash_flow | aging_report | vat_return | custom",
  "report_config": { "from": "YYYY-MM-DD", "to": "YYYY-MM-DD", "as_of": "YYYY-MM-DD", "currency": "EGP" },
  "schedule_type": "daily | weekly | monthly | quarterly",
  "schedule_day": 1,
  "schedule_time": "09:00",
  "format": "pdf | excel | csv",
  "recipients": ["finance@muhasebi.test", "owner@muhasebi.test"],
  "subject_template": "Monthly P&L — {{period}}",
  "is_active": true
}
```
**Response 201:** `{ "data": {}, "message": "Scheduled report created." }`

### `GET /v1/scheduled-reports/{scheduledReport}` — Show
**Response 200:** `{ "data": { "creator": {"id":1,"name":"...","email":"..."} } }`

### `PUT /v1/scheduled-reports/{scheduledReport}` — Update (same body as store)
### `DELETE /v1/scheduled-reports/{scheduledReport}` — Delete
### `POST /v1/scheduled-reports/{scheduledReport}/toggle` — Activate / deactivate
**Response 200:** `{ "data": {}, "message": "Scheduled report activated." }`
### `POST /v1/scheduled-reports/{scheduledReport}/send-now` — Send immediately out-of-cycle
**Response 200:** `{ "message": "Report sent successfully." }`

---

## Exports (Streaming CSV / Excel)

### `GET /v1/export/clients` — Export clients
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:view_reports, throttle:exports`
**Query params:** `format=csv|excel (default csv)`
**Response 200:** Streamed file `clients_YYYY-MM-DD.csv` (or `.xls`) with columns `ID, Name, Email, Phone, Tax ID, Address, Created At`.

### `GET /v1/export/invoices` — Export invoices
**Query params:** `from`, `to`, `status`, `format=csv|excel`
**Response 200:** Stream `invoices_YYYY-MM-DD.csv` — columns `Invoice #, Client, Date, Due Date, Subtotal, Tax, Total, Status, Paid Amount`.

### `GET /v1/export/journal-entries` — Export journal entries (line-expanded)
**Query params:** `from`, `to`, `format=csv|excel`
**Response 200:** Stream `journal_entries_YYYY-MM-DD.csv` — columns `Entry #, Date, Description, Account Code, Account Name, Debit, Credit`.
**Notes:** Streams memory-efficiently via cursor; safe for 100k+ rows.

---

## Anomaly Detection

### `GET /v1/anomalies` — Run all detectors
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:reports, permission:view_reports`
**Query params:** `from`, `to`
**Response 200:**
```json
{
  "data": {
    "duplicates": [],
    "unusual_amounts": [],
    "missing_sequences": [],
    "weekend_entries": []
  },
  "message": "Anomaly detection complete"
}
```

### `GET /v1/anomalies/duplicates` — Duplicate invoice detector
**Query params:** `from`, `to`
**Response 200:** `{ "data": [ { "invoice_a_id": 10, "invoice_b_id": 11, "reason": "same client + amount + date", "confidence": 0.95 } ] }`

### `GET /v1/anomalies/unusual-amounts` — Statistical outliers
**Query params:** `from`, `to`
**Response 200:** `{ "data": [ { "type": "invoice|bill|expense", "id": 42, "amount": "999999.00", "z_score": 4.2 } ] }`

### `GET /v1/anomalies/missing-sequences` — Gaps in doc numbering
**Query params:** `from`, `to`
**Response 200:** `{ "data": [ { "series": "INV", "missing": ["INV-2026-0011","INV-2026-0012"] } ] }`

### `GET /v1/anomalies/weekend-entries` — Journals posted Fri/Sat
**Query params:** `from`, `to`
**Response 200:** `{ "data": [ { "entry_number": "JE-00099", "date": "2026-03-14", "posted_by": "..." } ] }`
**Notes:** Weekend is Friday + Saturday (Egyptian business week).

---

## Related features
- **Tax Management** (`/v1/tax-returns/*`) — formal filings derived from the VAT / WHT reports above.
- **ETA E-Invoicing** — ETA-compliance dashboard at `/v1/eta/compliance-dashboard`.
- **Data Import** (`/v1/import/*`) — loading opening balances before running comparatives.
- **Approvals** — scheduled report deletions / custom report templates are NOT gated by approvals (direct CRUD).
