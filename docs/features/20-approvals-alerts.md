# Approval Workflows & Alerts

> Multi-step approval chains for bills, expenses, journal entries, leave requests, and payroll runs — plus threshold-based alert rules that notify stakeholders when business metrics cross configured limits.

## Access & Auth
- **Authentication:** Bearer token via Sanctum (`auth:sanctum`)
- **Tenant scope:** yes — all routes under the `tenant, active, enforce.2fa` group
- **Permissions required:**
  - `permission:manage_approvals` — approval workflow CRUD, submit/approve/reject
  - `permission:manage_alerts` — alert rule CRUD, toggle, history
- **Feature flags:** none (approvals and alerts are bundled with the base tenant features)
- **Rate limits:** none beyond the standard authenticated rate limits

---

## Approval Workflows

An **approval workflow** is a named, ordered set of steps that an entity must clear before it can be marked as approved. Each step has an approver type (`user`, `role`, or `manager`), an optional spend limit, and an optional timeout in hours.

Supported `entity_type` values:
- `bill`
- `expense`
- `journal_entry`
- `leave_request`
- `payroll_run`

### `GET /v1/approval-workflows` — List workflows
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:manage_approvals`
**Query params:** `entity_type`, `is_active (bool)`, `per_page (default 15, max 100)`
**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "name_ar": "اعتماد الفواتير الكبيرة",
      "name_en": "Large Bill Approval",
      "entity_type": "bill",
      "is_active": true,
      "steps_count": 2
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 1 }
}
```

### `POST /v1/approval-workflows` — Create workflow
**Request body:**
```json
{
  "name_ar": "اعتماد المصروفات فوق 5000 جنيه (required)",
  "name_en": "Expense Approval > 5000 EGP (optional)",
  "entity_type": "bill | expense | journal_entry | leave_request | payroll_run (required)",
  "is_active": true,
  "steps": [
    {
      "step_order": 1,
      "approver_type": "user | role | manager (required)",
      "approver_id": 12,
      "approval_limit": 10000.00,
      "timeout_hours": 48
    },
    {
      "step_order": 2,
      "approver_type": "role",
      "approver_id": 3,
      "approval_limit": null,
      "timeout_hours": 72
    }
  ]
}
```
**Response 201:** Newly created workflow with steps eager-loaded.
**Notes:** `approver_type=manager` resolves dynamically to the submitter's direct manager at runtime; `approver_type=role` requires `approver_id` to be a Spatie role id; `approver_type=user` requires a user id.

### `GET /v1/approval-workflows/{approvalWorkflow}` — Show workflow
**Response 200:** Workflow with `steps` relationship loaded.

### `PUT /v1/approval-workflows/{approvalWorkflow}` — Update workflow
**Request body:** Same shape as store (steps are replaced atomically).
**Response 200:** Updated workflow.

### `DELETE /v1/approval-workflows/{approvalWorkflow}` — Delete workflow
**Response 200:** `{ "message": "Workflow deleted." }`
**Notes:** Does NOT cascade — existing in-flight `ApprovalRequest` records retain a soft pointer to the workflow and continue to resolve.

---

## Approval Requests (entity lifecycle)

### `POST /v1/approvals/submit` — Submit entity for approval
**Purpose:** Starts a workflow run against an entity. The service looks up the first active workflow matching `entity_type` whose `approval_limit` rule is satisfied by `amount`.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:manage_approvals`
**Request body:**
```json
{
  "entity_type": "bill | expense | journal_entry | leave_request | payroll_run (required)",
  "entity_id": 42,
  "amount": 12500.00
}
```
**Response 201:**
```json
{
  "data": {
    "id": 88,
    "entity_type": "bill",
    "entity_id": 42,
    "workflow_id": 1,
    "status": "pending",
    "current_step": 1,
    "submitted_by": 5,
    "submitted_at": "2026-04-18T09:12:45Z"
  }
}
```
**Notes:** If no workflow matches, responds `200 { "message": "No matching workflow found." }` — the caller should treat this as "auto-approved" and proceed normally. Idempotency: resubmitting the same `(entity_type, entity_id)` while a pending request exists returns the existing request.

### `POST /v1/approvals/{approvalRequest}/approve` — Approve current step
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:manage_approvals`
**Request body:**
```json
{ "comment": "optional, max 2000 chars (Arabic or English)" }
```
**Response 200:**
```json
{
  "data": {
    "id": 88,
    "status": "in_progress | approved",
    "current_step": 2,
    "approvals": [
      { "step": 1, "approver_id": 12, "decision": "approved", "comment": "...", "acted_at": "2026-04-18T10:01:00Z" }
    ]
  }
}
```
**Notes:** When all steps are cleared, `status` flips to `approved` and the underlying entity transitions (e.g. bill becomes payable). Approvers can only act on their own assigned step.

### `POST /v1/approvals/{approvalRequest}/reject` — Reject the request
**Request body:**
```json
{ "comment": "سبب الرفض - required, max 2000 chars" }
```
**Response 200:** Approval request with `status: "rejected"` and the rejection comment recorded. The underlying entity is rolled back to draft / unapproved state.

### `GET /v1/approvals/pending` — Pending approvals assigned to me
**Response 200:**
```json
{
  "data": [
    {
      "id": 88,
      "entity_type": "bill",
      "entity_id": 42,
      "amount": 12500.00,
      "submitted_by": { "id": 5, "name": "Ahmed Fathi" },
      "current_step": 2,
      "timeout_at": "2026-04-21T09:12:45Z"
    }
  ]
}
```

### `GET /v1/approvals/history` — Approval history for an entity
**Query params:** `entity_type (required)`, `entity_id (int, required)`
**Response 200:** Array of past/current approval requests with full step decisions, timestamps, and approver identities.

### Statuses
`pending | in_progress | approved | rejected | cancelled`

---

## Alert Rules

An **alert rule** watches a business metric on a schedule and dispatches notifications when the value crosses a threshold. Rules have a per-rule cooldown to prevent notification spam.

**Supported metrics:** `dso`, `ar_total`, `ap_total`, `cash_balance`, `overdue_invoices_count`, `overdue_bills_count`, `vat_due_date`, `budget_utilization`, `collection_rate`.

**Operators:** `gt` (greater than), `gte`, `lt`, `lte`, `eq`.

### `GET /v1/alert-rules` — List rules
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:manage_alerts`
**Query params:** `is_active (bool)`, `metric`, `per_page (default 15, max 100)`
**Response 200:** Paginated `AlertRule[]`.

### `POST /v1/alert-rules` — Create rule
**Request body:**
```json
{
  "name_ar": "تنبيه تجاوز رصيد العملاء (required, max 255)",
  "name_en": "AR total exceeds threshold (optional)",
  "metric": "dso | ar_total | ap_total | cash_balance | overdue_invoices_count | overdue_bills_count | vat_due_date | budget_utilization | collection_rate",
  "operator": "gt | gte | lt | lte | eq",
  "threshold": 100000.00,
  "check_frequency": "hourly | daily | weekly (default daily)",
  "notification_channels": ["email", "push", "in_app"],
  "recipients": ["owner@muhasebi.test", "user_id:5"],
  "cooldown_hours": 24
}
```
**Response 201:**
```json
{
  "data": {
    "id": 7,
    "name_ar": "...", "metric": "ar_total", "threshold": "100000.00", "operator": "gt",
    "is_active": true, "last_triggered_at": null, "created_by": 1
  },
  "message": "Alert rule created."
}
```
**Notes:** `threshold` range is `-99999999999.99 … 99999999999.99`. `cooldown_hours` is clamped to `1..720` (30 days).

### `GET /v1/alert-rules/history` — Alert firing history
**Query params:** `alert_rule_id (int, optional)`, `per_page (max 100)`
**Response 200:** Paginated alert events: `{ alert_rule_id, fired_at, value, threshold, recipients_notified, channels }`.

### `GET /v1/alert-rules/{alertRule}` — Show rule
**Response 200:** Rule with `creator:id,name` eager-loaded.

### `PUT /v1/alert-rules/{alertRule}` — Update rule
**Request body:** Any subset of the store body; every field is `sometimes`.
**Response 200:** `{ "data": {...}, "message": "Alert rule updated." }`

### `DELETE /v1/alert-rules/{alertRule}` — Delete rule
**Response 200:** `{ "message": "Alert rule deleted." }`

### `POST /v1/alert-rules/{alertRule}/toggle` — Activate / deactivate
**Response 200:** `{ "data": {...}, "message": "Alert rule activated." }` (or deactivated).
**Notes:** Disabled rules are skipped by the scheduler but history is preserved.

---

## Related features
- **Bills** (`/v1/bills/{bill}/approve`) — bills use the generic approval system; a dedicated endpoint exists for the final approve step.
- **Expenses** (`/v1/expenses/{expense}/submit|approve|reject`) — mirrors the same workflow engine.
- **Leave requests** (`/v1/leave-requests/{leaveRequest}/approve|reject`) — HR flows.
- **Payroll** (`/v1/payroll/{payrollRun}/approve`) — payroll runs route through a dedicated workflow.
- **Notifications** (`/v1/notifications/*`) — both approval assignments and alert firings are delivered through the notification system.
