# Engagements & Working Papers (Professional Services)

> For accounting firms and consultants: manage client engagements, deliverables, budgets vs actuals, and audit-style working papers with a preparer/reviewer workflow.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_engagements`.
- **Feature flag:** none (engagements is an always-on professional-services module; availability is gated via plan).
- **Rate limits:** none.

## Engagements

### `GET /v1/engagements/dashboard`
Firm-level overview: active engagements, budget utilization, overdue deliverables, billable hours.

### `GET /v1/engagements`
**Query:** `client_id, status (planning|active|review|completed|archived), service_type, search, per_page`.

### `POST /v1/engagements`
```json
{
  "client_id": "int (required)",
  "title": "string (required)",
  "description": "string (optional)",
  "service_type": "audit | review | compilation | tax | advisory | bookkeeping | other",
  "start_date": "YYYY-MM-DD (required)",
  "end_date": "YYYY-MM-DD (optional)",
  "budget_hours": "number (optional)",
  "budget_amount": "number (optional, EGP)",
  "hourly_rate": "number (optional)",
  "partner_id": "int (optional, user)",
  "manager_id": "int (optional, user)",
  "team_ids": "int[] (optional)"
}
```

### `GET /v1/engagements/{engagement}`
```json
{
  "data": {
    "title": "...",
    "client": { ... },
    "status": "active",
    "budget_hours": 200,
    "actual_hours": 120,
    "budget_amount": 100000,
    "actual_cost": 62000,
    "utilization_percent": 62,
    "deliverables": [ ... ],
    "working_papers_count": 14
  }
}
```

### `PUT|DELETE /v1/engagements/{engagement}`

### `GET /v1/engagements/{engagement}/time-allocation`
Time breakdown by team member and phase.

---

## Deliverables

### `POST /v1/engagements/{engagement}/deliverables`
```json
{
  "title": "string (required)",
  "description": "string (optional)",
  "due_date": "YYYY-MM-DD (required)",
  "assigned_to": "int (optional, user)",
  "status": "pending | in_progress | review | completed (default pending)",
  "attachments": ["document_id (optional)"]
}
```

### `POST /v1/engagements/{engagement}/deliverables/{deliverable}/complete`
Mark complete.
```json
{ "notes": "string (optional)" }
```

---

## Working Papers (audit evidence)

### `GET /v1/engagements/{engagement}/working-papers`
List working papers for the engagement.
**Query:** `status, preparer_id, reviewer_id`.

### `POST /v1/engagements/{engagement}/working-papers`
Multipart (for file) or JSON.
```json
{
  "title": "string (required)",
  "reference_number": "string (required) — audit index like A.1, B.2",
  "preparer_id": "int (required, user)",
  "description": "string (optional)",
  "file": "file (optional)",
  "tags": ["string[]"]
}
```

### `PUT /v1/working-papers/{workingPaper}`
Update metadata (content locked after review).

### `POST /v1/working-papers/{workingPaper}/review`
Reviewer signs off.
```json
{
  "decision": "approved | rework | rejected (required)",
  "notes": "string (optional)",
  "signed_off_at": "YYYY-MM-DDTHH:MM:SSZ (optional, defaults to now)"
}
```

## Notes
- Time entries linked to an engagement (via timesheets) roll up to `actual_hours` / `actual_cost`.
- Working papers follow a standard audit-index scheme; reviewer sign-off is append-only and timestamped.
- Engagements with `status=active` surface on the timesheet entry dropdown for easy attribution.
