# Accounting Core — Chart of Accounts, Journal Entries, Fiscal Calendar

> Double-entry bookkeeping foundation: chart of accounts (tree), journal entries (draft→posted→reversed), recurring journal entries, fiscal years and periods (close/reopen). Also includes AI-powered account suggestion.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_accounts` — CoA CRUD, account suggestions, recurring JEs, bank recon, FX revaluation.
  - `manage_journal_entries` — JE CRUD, reverse.
  - `post_journal_entries` — post JE, fiscal year / period management.
- **Feature flag:** `accounting` on everything.
- **Rate limits:** none explicit.

## Chart of Accounts

### `GET /v1/accounts/tree`
Hierarchical view of accounts with computed balances.
**Response:** nested tree with `children[]`, `balance`, `debit_total`, `credit_total`.

### `GET /v1/accounts`
Flat paginated list.
**Query:** `type (Asset|Liability|Equity|Revenue|Expense), parent_id, search, per_page`.

### `POST /v1/accounts`
Create GL account.
```json
{
  "code": "string (required, unique per tenant) — e.g. 1110",
  "name_en": "string (required)",
  "name_ar": "string (required) — Arabic name for RTL display",
  "type": "Asset | Liability | Equity | Revenue | Expense (required)",
  "sub_type": "current_asset | fixed_asset | current_liability | ... (optional)",
  "parent_id": "int (optional)",
  "currency": "string (optional, default EGP)",
  "is_bank": "bool (optional, default false)",
  "description": "string (optional)",
  "opening_balance": "number (optional)",
  "opening_balance_date": "YYYY-MM-DD (optional, defaults to FY start)"
}
```

### `GET /v1/accounts/{account}`
Full detail with transaction summary.

### `PUT /v1/accounts/{account}` / `DELETE /v1/accounts/{account}`
Update / soft-delete. Accounts with posted transactions cannot be deleted, only archived.

### `POST /v1/import/accounts`
CSV bulk upload. `file` multipart.

---

## Account Suggestions (AI categorization)

### `GET /v1/account-suggestions`
**Query:** `description=Electricity bill&amount=1200&type=expense`
Returns ranked suggestions with confidence scores.
```json
{ "data": [ { "account_id": 5112, "confidence": 0.92, "reason": "matched keyword 'electricity'" } ] }
```

### `POST /v1/account-suggestions/train`
Feed training signal when a user picks an account.
```json
{ "description": "string", "amount": 1200, "chosen_account_id": 5112 }
```

---

## Journal Entries

### `GET /v1/journal-entries`
**Query:** `from_date, to_date, status (draft|posted|reversed), reference, account_id, cost_center_id, per_page, search`.

### `POST /v1/journal-entries`
Create a draft JE.
```json
{
  "date": "YYYY-MM-DD (required)",
  "description": "string (required)",
  "reference": "string (optional)",
  "currency": "string (optional, default EGP)",
  "exchange_rate": "number (optional, defaults to day rate)",
  "lines": [
    {
      "account_id": "int (required)",
      "debit": "number (>=0, exactly one of debit/credit > 0)",
      "credit": "number (>=0)",
      "description": "string (optional)",
      "cost_center_id": "int (optional)",
      "project_id": "int (optional)"
    }
  ]
}
```
**Rule:** sum(debits) = sum(credits). Violations return 422.

### `GET /v1/journal-entries/{journalEntry}`
Full detail with lines, status history, related entries (posting, reversal).

### `PUT /v1/journal-entries/{journalEntry}`
Update draft. Posted JEs are immutable (use reverse).

### `DELETE /v1/journal-entries/{journalEntry}`
Delete draft.

### `POST /v1/journal-entries/{journalEntry}/post`
**Permission:** `post_journal_entries`
Posts the entry. Applies balance to accounts, moves status to `posted`, writes audit event.

### `POST /v1/journal-entries/{journalEntry}/reverse`
Creates an offsetting JE dated today (or provided `date`).
```json
{ "date": "YYYY-MM-DD (optional)", "reason": "string (optional)" }
```

### `POST /v1/import/opening-balances`
Bulk opening balance import (CSV). Creates an opening-balance JE per fiscal year.

---

## Recurring Journal Entries

### `GET /v1/recurring-journal-entries`
List all templates.

### `POST /v1/recurring-journal-entries`
```json
{
  "name": "string (required)",
  "description": "string (optional)",
  "frequency": "daily | weekly | monthly | quarterly | yearly (required)",
  "start_date": "YYYY-MM-DD (required)",
  "end_date": "YYYY-MM-DD (optional)",
  "lines": [ /* same shape as JE lines */ ]
}
```

### `GET|PUT|DELETE /v1/recurring-journal-entries/{recurringJournalEntry}`
Standard resource endpoints.

### `POST /v1/recurring-journal-entries/{recurringJournalEntry}/toggle`
Enable / disable the template without deleting it.

---

## Fiscal Year & Period (`permission: post_journal_entries`)

### `GET /v1/fiscal-years`
List fiscal years. Paginated.

### `POST /v1/fiscal-years`
```json
{
  "name": "FY2026",
  "start_date": "2026-01-01",
  "end_date": "2026-12-31",
  "auto_generate_periods": true,
  "period_length": "monthly | quarterly"
}
```

### `GET /v1/fiscal-years/{fiscalYear}`
Detail with periods and closing status.

### `POST /v1/fiscal-periods/{fiscalPeriod}/close`
Closes the period. Blocks further postings into it (unless reopened). Runs P&L-to-retained-earnings closing entry on year-end periods.

### `POST /v1/fiscal-periods/{fiscalPeriod}/reopen`
Reverses the close. Audit-logged.

## Notes
- All posted JEs are immutable; corrections happen via reversal or a new offsetting JE.
- The `enforce.2fa` middleware means destructive accounting operations require the user to have verified 2FA in their current session.
- Multi-currency: lines carry their natural currency; amounts are stored in both source and functional (tenant base) currency with the applied `exchange_rate`.
