# Timesheets & Time Tracking

> Weekly timesheets with approval workflow, live timers, and time-to-invoice billing for professional-services engagements.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_timesheets` — CRUD, submit, reject, timers, time-billing.
  - `approve_timesheets` — approve, bulk-approve.
- **Feature flag:** `timesheets`.
- **Rate limits:** none.

## Timesheets

### `GET /v1/timesheets/summary`
Aggregated hours for the current user or team.
**Query:** `employee_id, week_start, week_end, status`.

### `GET /v1/timesheets`
**Query:** `employee_id, status (draft|submitted|approved|rejected), from_date, to_date, per_page`.

### `POST /v1/timesheets`
```json
{
  "employee_id": "int (required)",
  "week_start": "YYYY-MM-DD (required) — Sunday or Monday per locale",
  "notes": "string (optional)",
  "entries": [
    {
      "date": "YYYY-MM-DD",
      "hours": 8.0,
      "client_id": "int (optional)",
      "engagement_id": "int (optional)",
      "project_id": "int (optional)",
      "task": "string (optional)",
      "billable": true,
      "description": "string (optional)"
    }
  ]
}
```

### `GET /v1/timesheets/{timesheet}`
Detail with day-by-day entries, billable/non-billable split.

### `PUT /v1/timesheets/{timesheet}`
Edit (only while `draft` or `rejected`).

### `DELETE /v1/timesheets/{timesheet}`

### `POST /v1/timesheets/{timesheet}/submit`
Submit for approval.

### `POST /v1/timesheets/{timesheet}/approve`
**Permission:** `approve_timesheets`
```json
{ "notes": "string (optional)" }
```

### `POST /v1/timesheets/{timesheet}/reject`
```json
{ "reason": "string (required)" }
```

### `POST /v1/timesheets/bulk-approve`
**Permission:** `approve_timesheets`
```json
{ "timesheet_ids": "int[]" }
```

### `POST /v1/timesheets/bulk-submit`
```json
{ "timesheet_ids": "int[]" }
```

---

## Timers (live tracking)

### `POST /v1/timers/start`
Start a timer for the current user.
```json
{
  "description": "string (optional)",
  "client_id": "int (optional)",
  "engagement_id": "int (optional)",
  "project_id": "int (optional)",
  "task": "string (optional)",
  "billable": true
}
```

### `GET /v1/timers/current`
The running timer for the user, if any.
```json
{ "data": { "id": 1, "started_at": "...", "elapsed_seconds": 1234, "description": "..." } }
```

### `POST /v1/timers/{timer}/stop`
Stop and convert to a timesheet entry.
```json
{ "timesheet_id": "int (optional) — defaults to current week" }
```

### `DELETE /v1/timers/{timer}`
Discard the running timer without recording.

---

## Time Billing

### `GET /v1/time-billing/preview`
Preview billable time ready to invoice for a client.
**Query:** `client_id, from_date, to_date`.
**Response:** `{ data: { total_hours, total_amount, entries[] } }`.

### `POST /v1/time-billing/generate`
Generate a draft invoice from billable time.
```json
{
  "client_id": "int (required)",
  "from_date": "YYYY-MM-DD",
  "to_date": "YYYY-MM-DD",
  "hourly_rate": "number (required) — EGP per hour",
  "description_template": "string (optional)",
  "create_invoice": true
}
```
Returns the draft invoice id.

## Notes
- Only `approved` timesheet entries flagged `billable=true` are eligible for time-to-invoice.
- Weeks start per tenant locale preference (Sunday for Egypt default).
- Timers auto-stop at midnight if forgotten (configurable).
