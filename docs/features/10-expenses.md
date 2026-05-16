# Expenses & Expense Reports

> Employee expense tracking with categories, approvals, reimbursement, and bundled expense reports.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_expenses` on every endpoint.
- **Feature flag:** `expenses`.
- **Rate limits:** none explicit.

## Expense Categories

### `GET /v1/expense-categories`
List.

### `POST /v1/expense-categories`
```json
{
  "name_ar": "string (required)",
  "name_en": "string (optional)",
  "code": "string (optional, unique)",
  "description": "string (optional)",
  "account_id": "int (optional) — default GL expense account",
  "color": "string (optional, hex)"
}
```

### `GET|PUT|DELETE /v1/expense-categories/{expenseCategory}`
Standard resource operations.

---

## Expenses

### `GET /v1/expenses`
**Query:** `status (draft|submitted|approved|rejected|reimbursed), category_id, employee_id, from_date, to_date, per_page, search`.

### `POST /v1/expenses`
Multipart (for receipt upload) OR JSON.
```json
{
  "expense_category_id": "int (required)",
  "amount": "number (required)",
  "currency": "string (optional, default EGP)",
  "exchange_rate": "number (optional)",
  "date": "YYYY-MM-DD (required)",
  "vendor_name": "string (optional)",
  "vendor_id": "int (optional)",
  "description": "string (required)",
  "cost_center_id": "int (optional)",
  "project_id": "int (optional)",
  "payment_method": "cash | bank_transfer | company_card | personal (required)",
  "vat_rate": "number (optional)",
  "receipt": "file (optional, jpg/png/pdf ≤ 10MB)",
  "notes": "string (optional)"
}
```

### `GET /v1/expenses/{expense}`
Full detail with receipt URL, approval chain, GL posting.

### `PUT /v1/expenses/{expense}`
Update draft.

### `DELETE /v1/expenses/{expense}`
Soft-delete draft.

### `POST /v1/expenses/{expense}/submit`
Submit for approval. Moves to `submitted` state; routes through approval workflow if configured.

### `POST /v1/expenses/{expense}/approve`
Approve an expense.
```json
{ "notes": "string (optional)" }
```

### `POST /v1/expenses/{expense}/reject`
```json
{ "reason": "string (required)" }
```

### `POST /v1/expenses/{expense}/reimburse`
Mark as reimbursed and create payment entry.
```json
{
  "payment_date": "YYYY-MM-DD (required)",
  "bank_account_id": "int (required)",
  "reference": "string (optional)"
}
```

### `POST /v1/expenses/bulk-submit`
```json
{ "expense_ids": "int[]" }
```

### `GET /v1/expenses/reports/summary`
Aggregated expense totals by category, employee, cost center.
**Query:** `from_date, to_date, group_by`.

---

## Expense Reports (bundles)

### `GET /v1/expense-reports`
List.

### `POST /v1/expense-reports`
```json
{
  "title": "string (required)",
  "description": "string (optional)",
  "period_from": "YYYY-MM-DD (required)",
  "period_to": "YYYY-MM-DD (required)",
  "expense_ids": "int[] (required)",
  "notes": "string (optional)"
}
```

### `GET /v1/expense-reports/{expenseReport}`
Detail with included expenses and aggregate totals.

### `POST /v1/expense-reports/{expenseReport}/expenses`
Add more expenses to an existing report.
```json
{ "expense_ids": "int[]" }
```

### `POST /v1/expense-reports/{expenseReport}/submit`
Submit the full report.

### `POST /v1/expense-reports/{expenseReport}/approve`
Approve the full report (all expenses transition together).

### `POST /v1/expense-reports/{expenseReport}/reject`
```json
{ "reason": "string (required)" }
```

## Notes
- Receipts accept `jpg, jpeg, png, pdf` up to 10 MB per file.
- Approval chains defined in Approval Workflows (see `20-approvals-alerts.md`); an expense without a workflow match auto-approves to the submitter's manager.
- GL posting on approval: DR Expense Category (or vendor line), CR Cash / Credit Card / Employee Payable.
- Reimbursement creates a separate bank-payment JE.
