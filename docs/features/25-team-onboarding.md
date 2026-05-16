# Team & Onboarding

> Invite teammates, assign roles, and guide first-time tenants through a setup wizard (chart of accounts template, fiscal year, sample data, first team invite).

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_team` — team management.
  - `manage_onboarding` — classic onboarding endpoints.
  - Onboarding Wizard routes require only `tenant, active` (no permission — any tenant admin during trial).
- **Feature flag:** none.
- **Rate limits:** none.

## Team

### `GET /v1/team`
List tenant users with roles.
**Query:** `status, role, search`.

### `POST /v1/team/invite`
```json
{
  "email": "string (required, email)",
  "name": "string (required)",
  "role": "tenant_admin | accountant | manager | employee | viewer (required)",
  "send_email": true
}
```
Returns the created user + invitation link.

### `PUT /v1/team/{user}`
```json
{ "name": "string (optional)", "phone": "string (optional)" }
```

### `PATCH /v1/team/{user}/toggle-active`
Deactivate/reactivate a team member.

### `DELETE /v1/team/{user}`
Remove from tenant (user record retained for audit).

### `PUT /v1/team/{user}/role`
```json
{ "role": "string (required)" }
```

---

## Classic Onboarding

### `GET /v1/onboarding/progress`
```json
{ "data": { "steps": [ { "key": "setup_coa", "completed": true } ], "completed_count": 3, "total_steps": 7 } }
```

### `POST /v1/onboarding/complete-step`
```json
{ "step": "string (required)" }
```

### `POST /v1/onboarding/skip`
Skip the remaining tour.

### `POST /v1/onboarding/setup-coa`
```json
{
  "template_id": "int (optional) — one of the built-in CoA templates",
  "manual_accounts": [ { "code": "...", "name_ar": "...", "name_en": "...", "type": "Asset|..." } ]
}
```

### `POST /v1/onboarding/setup-fiscal-year`
```json
{ "start_date": "2026-01-01", "end_date": "2026-12-31", "period_length": "monthly | quarterly" }
```

### `POST /v1/onboarding/load-sample-data`
Populate demo data (clients, products, invoices) so the user can explore before entering real records.

### `POST /v1/onboarding/invite-team-member`
Shortcut wrapping `/v1/team/invite` inside the wizard.

---

## Onboarding Wizard (newer, self-serve)

### `GET /v1/onboarding-wizard/progress`
Multi-step wizard state machine.

### `GET /v1/onboarding-wizard/templates`
Available industry templates (retail, services, manufacturing, professional firm).

### `POST /v1/onboarding-wizard/select-template`
```json
{ "template_id": "int (required)" }
```

### `POST /v1/onboarding-wizard/import-balances`
```json
{
  "opening_balances": [
    { "account_id": "int", "debit": "number", "credit": "number" }
  ]
}
```

### `POST /v1/onboarding-wizard/complete-step`
```json
{ "step": "string (required)" }
```

### `POST /v1/onboarding-wizard/skip-step`
```json
{ "step": "string (required)", "reason": "string (optional)" }
```

## Notes
- Role definitions and permission maps are managed in the `permissions` config; custom roles aren't exposed via the public API yet.
- The wizard persists partial state, so users can close and resume.
- Industry templates install a starter chart of accounts plus sample VAT / WHT rates appropriate to the activity type.
