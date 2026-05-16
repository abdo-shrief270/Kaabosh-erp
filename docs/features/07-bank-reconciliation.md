# Bank Reconciliation, Bank Connections & FX Revaluation

> Reconcile bank statements against GL activity with AI-assisted matching, manage live bank connections to Egyptian banks, build and apply categorization rules, and revalue foreign-currency balances at period end.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_accounts` on all endpoints in this module.
- **Feature flag:** `accounting`.
- **Rate limits:** none explicit.

## Bank Reconciliations

### `GET /v1/bank-reconciliations`
List.
**Query:** `account_id, status (open|completed), from_date, to_date, per_page`.

### `POST /v1/bank-reconciliations`
Open a new reconciliation against a bank account.
```json
{
  "account_id": "int (required) — must be a bank GL account",
  "statement_date": "YYYY-MM-DD (required)",
  "statement_balance": "number (required)",
  "notes": "string (optional)"
}
```

### `GET /v1/bank-reconciliations/{bankReconciliation}`
Full detail with statement lines, matched status, outstanding items.

### `DELETE /v1/bank-reconciliations/{bankReconciliation}`
Delete a reconciliation in `open` state. Completed reconciliations cannot be deleted.

### `GET /v1/bank-reconciliations/{bankReconciliation}/summary`
```json
{
  "data": {
    "statement_balance": 250000.00,
    "gl_balance": 249800.00,
    "variance": 200.00,
    "matched_lines": 42,
    "unmatched_lines": 3,
    "outstanding_deposits": 500.00,
    "outstanding_checks": 300.00
  }
}
```

### `POST /v1/bank-reconciliations/{bankReconciliation}/import`
Import statement lines.
```json
{
  "lines": [
    {
      "date": "YYYY-MM-DD",
      "description": "string",
      "reference": "string (optional)",
      "amount": "number",
      "type": "debit | credit"
    }
  ]
}
```
Also accepts multipart CSV/OFX/MT940 when `Content-Type: multipart/form-data`.

### `POST /v1/bank-reconciliations/{bankReconciliation}/auto-match`
Run deterministic matcher (amount+date window).

### `POST /v1/bank-reconciliations/{bankReconciliation}/smart-match`
Run AI / fuzzy matcher (description similarity, partial amount). Response lists matches with confidence.

### `POST /v1/bank-reconciliations/{bankReconciliation}/auto-post`
Automatically post the GL entries for high-confidence matches.

### `POST /v1/bank-reconciliations/{bankReconciliation}/categorize`
Apply categorization rules (see below) to uncategorized lines.

### `POST /v1/bank-reconciliations/{bankReconciliation}/complete`
Marks recon closed. Variance must be zero or have a documented offsetting JE.

---

## Statement Line Actions

### `POST /v1/bank-reconciliations/lines/{bankStatementLine}/match`
Manually match a statement line to a GL transaction.
```json
{ "transaction_type": "journal_entry | invoice | bill | payment", "transaction_id": 42 }
```

### `POST /v1/bank-reconciliations/lines/{bankStatementLine}/unmatch`
Undo a match.

### `POST /v1/bank-reconciliations/lines/{bankStatementLine}/exclude`
Skip this line (internal transfer, reversal, error).
```json
{ "reason": "string" }
```

### `POST /v1/bank-statement-lines/{bankStatementLine}/match-invoice`
```json
{ "invoice_id": "int" }
```

### `POST /v1/bank-statement-lines/{bankStatementLine}/match-bill`
```json
{ "bill_id": "int" }
```

### `GET /v1/bank-statement-lines/{bankStatementLine}/suggestions`
Returns candidate matches with confidence scores.

### `POST /v1/bank-statement-lines/{bankStatementLine}/apply-suggestion`
```json
{ "suggestion_id": "uuid" }
```

### `POST /v1/bank-statement-lines/{bankStatementLine}/learn`
Train the categorization model on a user's decision (description → account).

---

## Bank Categorization Rules

### `GET /v1/bank-categorization-rules`
List saved rules.

### `POST /v1/bank-categorization-rules`
```json
{
  "name": "string (required)",
  "pattern": "string (required) — regex or wildcard applied to line description",
  "account_id": "int (required)",
  "cost_center_id": "int (optional)",
  "tax_rate": "number (optional)",
  "active": true
}
```

### `DELETE /v1/bank-categorization-rules/{bankCategorizationRule}`
Delete.

---

## Bank Connections (live bank API integrations)

### `GET /v1/bank-connections/dashboard`
Summary across all connections: last sync, errors, unreconciled count.

### `GET /v1/bank-connections/supported-formats`
List of supported Egyptian banks / formats (MT940, OFX, CBE, NBE, CIB, AAIB, etc.).

### `POST /v1/bank-connections/generate-instruction`
Generate the bank-specific authorization letter / instruction doc.
```json
{ "bank_slug": "nbe | cib | aaib | ...", "account_id": 42 }
```

### `GET|POST|PUT|DELETE /v1/bank-connections(/{bankConnection})`
CRUD.
**Store body:**
```json
{
  "account_id": "int (required)",
  "bank_slug": "string (required)",
  "format": "mt940 | ofx | csv | api",
  "credentials": { "api_key": "...", "secret": "..." },
  "schedule": "manual | daily | hourly"
}
```

### `POST /v1/bank-connections/{bankConnection}/sync-balance`
Pulls current balance only.

### `POST /v1/bank-connections/{bankConnection}/import-statement`
Pulls statement lines for a date range.
```json
{ "from_date": "YYYY-MM-DD", "to_date": "YYYY-MM-DD" }
```

---

## FX Revaluation

### `GET /v1/fx-revaluations`
List revaluation runs.

### `POST /v1/fx-revaluations`
Calculate unrealized FX gain/loss for selected accounts.
```json
{
  "reference_date": "YYYY-MM-DD (required)",
  "accounts": "int[] (required) — foreign-currency accounts to revalue",
  "rate_source": "manual | cbe (default cbe)",
  "manual_rates": { "USD": 48.55, "EUR": 52.10 }
}
```
Returns computed adjustments per account.

### `GET /v1/fx-revaluations/{fxRevaluation}`
Detail with line-by-line breakdown.

### `POST /v1/fx-revaluations/{fxRevaluation}/post`
Post the unrealized FX gain/loss JE.

### `POST /v1/fx-revaluations/{fxRevaluation}/reverse`
Reverse the posting (usually first day of next period).

## Notes
- All recon workflows are tenant-scoped and auditable — every match/unmatch/exclude is logged.
- Suggestions use a lightweight on-tenant classifier trained from user feedback (`/learn` endpoint).
- FX rates default to CBE (Central Bank of Egypt); manual override supported.
