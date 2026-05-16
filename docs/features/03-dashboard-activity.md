# Core Dashboard, Activity & Notifications

> Tenant home dashboard, activity log (audit trail), compliance audit views, and in-app notifications.

## Access & Auth
- **Authentication:** Bearer token (Sanctum).
- **Tenant scope:** yes — all routes under `tenant, active, enforce.2fa, set_timezone, set_locale, meter.usage`.
- **Permissions:** `view_dashboard` (dashboard), `view_audit` (audit-compliance). Activity log and notifications require no permission (every authenticated tenant user sees their own notifications and can view the activity log).
- **Feature flags:** `audit_log` gates the audit-compliance block.
- **Rate limits:** none.

## Endpoints

### `GET /v1/dashboard`
**Purpose:** fetch the tenant's executive snapshot.
**Middleware:** `permission:view_dashboard`
**Query:**
- `period` — `today | this_week | this_month | this_quarter | ytd` (default `this_month`)
- `currency` — override display currency (default tenant base currency).

**Response 200:**
```json
{
  "data": {
    "metrics": {
      "revenue": 1240000,
      "expenses": 430000,
      "profit": 810000,
      "cash_on_hand": 2100000,
      "outstanding_ar": 680000,
      "outstanding_ap": 215000
    },
    "charts": {
      "revenue_trend": [ { "date": "2026-04-01", "value": 42000 } ],
      "top_clients": [ { "client_id": 12, "name": "...", "amount": 180000 } ]
    },
    "alerts": [ { "type": "overdue_invoices", "count": 7 } ]
  }
}
```

---

## Activity Log

### `GET /v1/activity-log`
Paginated activity stream across the tenant (who did what, when).
**Query:** `user_id, entity_type, action, from_date, to_date, per_page`.

### `GET /v1/activity-log/stats`
Aggregations: activity per user, per action type, per day.

### `GET /v1/activity-log/{activityId}`
Single activity with full properties (before/after state).

**Response shape:**
```json
{ "data": { "id": 1, "user": {...}, "action": "invoice.posted", "entity_type": "Invoice", "entity_id": 42, "properties": { "old": {...}, "new": {...} }, "ip": "...", "user_agent": "...", "created_at": "..." } }
```

---

## Audit Compliance (`feature:audit_log`, `permission:view_audit`)

Read-only compliance views for internal audit / external auditors.

### `GET /v1/audit-compliance/user-access`
Access log per user: logins, permission changes, sensitive resource views.
**Query:** `user_id, from_date, to_date`.

### `GET /v1/audit-compliance/changes`
Record-level change log (inserts, updates, deletes) across audited entities.
**Query:** `entity_type, entity_id, from_date, to_date, user_id`.

### `GET /v1/audit-compliance/high-risk`
Transactions flagged as high-risk (large amounts, weekend entries, repeated edits).

### `GET /v1/audit-compliance/segregation`
Segregation-of-duties violations (same user entered and approved, etc.).

### `GET /v1/audit-compliance/export`
CSV export of filtered audit data. Same filters as above.

### `GET /v1/audit-compliance/summary`
Period summary for audit reports: counts by risk bucket, top actors, entity hotspots.

---

## Notifications (in-app)

### `GET /v1/notifications`
Paginated notifications for the current user.
**Query:** `read, type, per_page`.

### `GET /v1/notifications/unread-count`
`{ "data": { "count": 7 } }` — poll for badge.

### `POST /v1/notifications/{notification}/read`
Marks one notification as read.

### `POST /v1/notifications/read-all`
Marks all as read.

### `DELETE /v1/notifications/{notification}`
Delete one notification.

**Notification shape:**
```json
{
  "id": "uuid",
  "type": "App\\Notifications\\InvoicePaid",
  "data": { "invoice_id": 42, "amount": 5000, "currency": "EGP" },
  "read_at": null,
  "created_at": "..."
}
```

## Notes
- Activity log is read-only — writes happen automatically via model observers.
- Audit compliance views are designed for "give me everything between these two dates" auditor requests; export endpoint returns CSV when `Accept: text/csv` is sent.
- Notification channel fan-out (email/SMS/push) is controlled per-event by the user's notification preferences (see `01-authentication.md`).
