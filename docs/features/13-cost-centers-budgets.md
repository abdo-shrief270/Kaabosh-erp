# Cost Centers & Budgeting

> Multi-dimensional cost tracking (departments, projects, locations) plus annual budgets with variance analysis.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_cost_centers` — cost center CRUD + reports.
  - `manage_accounts` — budgets.
- **Feature flags:** `cost_centers`, `budgeting`.
- **Rate limits:** none.

## Cost Centers

### `GET /v1/cost-centers`
**Query:** `parent_id, manager_id, search, active, per_page`.

### `POST /v1/cost-centers`
```json
{
  "code": "string (required, unique)",
  "name_en": "string (required)",
  "name_ar": "string (required)",
  "parent_id": "int (optional) — for hierarchical CCs",
  "description": "string (optional)",
  "manager_id": "int (optional) — user/employee",
  "type": "department | project | location | division (optional)",
  "active": true
}
```

### `GET|PUT|DELETE /v1/cost-centers/{costCenter}`

### `GET /v1/cost-centers/{costCenter}/pnl`
P&L for a single cost center.
**Query:** `from_date, to_date, include_children (bool)`.
**Response:**
```json
{
  "data": {
    "revenue": 1200000,
    "expenses": 850000,
    "contribution": 350000,
    "margin_percent": 29.17,
    "line_items": [ { "account": "...", "amount": 120000 } ]
  }
}
```

### `GET /v1/cost-centers/reports/cost-analysis`
Cross-CC cost analysis.
**Query:** `from_date, to_date, group_by (account|cost_center|month)`.

### `GET /v1/cost-centers/reports/allocation`
Allocated vs direct cost report.

---

## Budgets (`feature:budgeting`, `permission:manage_accounts`)

### `GET /v1/budgets`
List.

### `POST /v1/budgets`
```json
{
  "name": "string (required) — e.g. 'FY2026 Operating Budget'",
  "fiscal_year_id": "int (required)",
  "budget_period": "month | quarter | year (required)",
  "scope": "tenant | cost_center (optional, default tenant)",
  "cost_center_id": "int (optional, required if scope=cost_center)",
  "notes": "string (optional)"
}
```

### `GET /v1/budgets/{budget}`
Detail with all lines and rollups.

### `PUT|DELETE /v1/budgets/{budget}`

### `POST /v1/budgets/{budget}/lines`
Set budget lines (replaces existing set).
```json
{
  "lines": [
    {
      "account_id": "int (required)",
      "cost_center_id": "int (optional)",
      "period": "YYYY-MM",
      "amount": "number (required)"
    }
  ]
}
```

### `POST /v1/budgets/{budget}/approve`
Lock the budget (no more edits unless reopened).

### `GET /v1/budgets/{budget}/variance`
Budget vs actual.
**Query:** `as_of_date, group_by (account|period|cost_center)`.
**Response:**
```json
{
  "data": {
    "total_budget": 5000000,
    "total_actual": 4725000,
    "variance": 275000,
    "variance_percent": 5.5,
    "lines": [ { "account": "...", "budget": 120000, "actual": 118500, "variance_pct": 1.25 } ]
  }
}
```

## Notes
- Cost centers can be attached to JE lines, invoice lines, bill lines, expenses, timesheet entries — they flow to every module.
- Hierarchical CCs roll up: parent P&L = sum of children's + direct postings.
- Budget variance is computed live from GL — no reporting lag.
