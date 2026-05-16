# Backend TODOs — surfaced from frontend integration questions

Follow-ups surfaced during the review in `OPEN-QUESTIONS.md`. Solo-dev punch list; prioritised by risk.

---

## P0 — Correctness / money-safety

### ~~Idempotency coverage is incomplete~~ ✅ Done
Extended `idempotent` middleware to all financial-write endpoints. Done-by commit for this item noted inline:

- [x] `POST /invoices` (apiResource + cancel/post-to-gl/credit-note)
- [x] `POST /invoices/{id}/send`
- [x] `POST /payments`
- [x] `POST /bills` (apiResource + approve/cancel)
- [x] `POST /bills/{id}/payments`
- [x] `POST /payroll/{id}/calculate|approve|mark-paid`
- [x] `POST /eta/documents/{invoice}/submit|cancel`
- [x] `POST /subscription/change-plan`
- [x] `POST /import`, `POST /import/clients`, `POST /import/accounts`, `POST /import/opening-balances`
- [x] Also extended to `journal-entries` (apiResource + reverse + post) since misposting is expensive to undo
- [x] Regression test added at `tests/Feature/IdempotencyMiddlewareTest.php` (5 assertions: no-key pass-through, replay preserves status, bad-UUID 422, GET skipped, failed responses not cached)

### ~~2FA flow does not match docs~~ ✅ Done (option a)
Login response now includes `requires_2fa: bool`. True when user is admin/super-admin without 2FA enabled — exactly matches `Enforce2fa` middleware's gating condition, so the login flag and the downstream 403 stay in sync. Token remains full-access; non-admins bypass the flag entirely.

- [x] `AuthController::login()` returns `data.requires_2fa`
- [x] `docs/features/01-authentication.md` rewritten to describe actual behavior (flag semantics, no token scoping)
- [x] 3 regression tests in `AuthTest.php` (admin w/o 2FA → true; admin w/ 2FA → false; non-admin → false)

Option (b) — token scoping + per-session verification — deferred. Current model is "once 2FA enabled, always allowed" which is weaker than per-session verification but matches what the `Enforce2fa` middleware actually enforces. If per-session verification becomes a requirement (e.g. for SOC2), revisit this with a new ticket.

### ~~E-commerce webhook has no signature verification~~ ✅ Done
Route changed to `POST /webhooks/ecommerce/{platform}/{channel}` — channel id identifies tenant + webhook_secret. New `VerifyEcommerceWebhookSignature` middleware rejects unsigned / wrong-signature / inactive-channel requests with 401.

- [x] Middleware at `app/Http/Middleware/VerifyEcommerceWebhookSignature.php` registered as `ecommerce.verify`
- [x] Platform schemes wired:
  - Shopify: `X-Shopify-Hmac-Sha256` base64 HMAC-SHA256
  - WooCommerce: `X-WC-Webhook-Signature` base64 HMAC-SHA256
  - Salla: `X-Salla-Signature` hex HMAC-SHA256
  - Zid: `X-Zid-Signature` hex HMAC-SHA256
  - Custom: always rejected (use an authenticated endpoint for self-hosted integrations)
- [x] Webhook secret stored encrypted on `ECommerceChannel.webhook_secret` (was already there)
- [x] 11 regression tests cover: valid-per-platform, wrong-sig, missing-sig, missing-secret, unknown-channel, platform-mismatch, inactive-channel, custom rejection
- [x] Middleware binds `tenant.id` and exposes verified channel via `$request->attributes->get('ecommerce_channel')` for downstream handlers

Known gap: the actual event-processing logic in `ECommerceService::webhookHandler` is still placeholders — signatures are now verified, but order records aren't yet created from webhook payloads. Separate ticket when real sync is implemented.

---

## P1 — Gaps blocking clean frontend integration

### ~~`/v1/me` missing `tenant.features`~~ ✅ Done
`/v1/me` now returns `data.tenant.features` as a `{key: bool}` map covering every flag defined in the `feature_flags` table, with per-tenant overrides merged in. The plan id is resolved from the tenant's active (or trial) subscription, so plan-bundled flags light up correctly.

- [x] `AuthController::me()` calls `FeatureFlagService::getAllForTenant($tenantId, $planId)` via a private helper
- [x] `FeatureFlagService` already caches the merged map per tenant for 5 minutes — `/me` polling won't thrash the DB
- [x] Regression test in `AuthTest.php` asserts the map structure + merge semantics with one globally-enabled and one globally-disabled flag
- [x] Docs updated in `docs/features/01-authentication.md` — noted that `features` is included on `tenant`

### ~~Permissions missing from backend~~ ✅ Done
Added `manage_engagements`, `manage_approvals`, `manage_alerts` to `config/permissions.php`. The routes already referenced these slugs (`routes/api.php:661,883,896`) but the slugs weren't defined anywhere, so `CheckPermission` middleware was denying non-super-admins. Now they seed correctly.

- [x] `manage_engagements` → admin + accountant
- [x] `manage_approvals` → admin + accountant
- [x] `manage_alerts` → admin only (alerts are a platform-ops concern, not day-to-day accounting)
- [x] `manage_reports` → **not added.** Reports are read-only for tenant users today; `view_reports` covers the existing behavior. When report templates/schedules become user-editable, re-evaluate. Frontend should drop `manage_reports` from `app/core/rbac/permissions.ts`.

Seeder is config-driven — `php artisan db:seed --class=PermissionSeeder --force` on deploy picks them up. Existing tenants with pre-assigned roles need a re-sync: run the seeder on the production DB once.

### ~~Role-to-permission preset endpoint~~ ✅ Done
`GET /v1/rbac/role-presets` returns the admin/accountant/auditor preset maps directly from `config('permissions')`, gated by `permission:manage_team`. Includes English + Arabic labels so the frontend can render a bilingual dropdown without a second translation fetch.

Response shape:
```json
{
  "data": [
    { "role": "admin", "label": "Administrator", "label_ar": "مدير", "permissions": [...] },
    { "role": "accountant", "label": "Accountant", "label_ar": "محاسب", "permissions": [...] },
    { "role": "auditor", "label": "Auditor", "label_ar": "مراجع", "permissions": [...] }
  ]
}
```

- [x] `RbacController::rolePresets` + route at `api.php`
- [x] 2 tests in `tests/Feature/RbacPresetsTest.php` (happy path + permission rejection)

---

## P2 — Consistency / polish

### ~~Canonical bulk-response pattern~~ ✅ Done
`POST /api/v1/ecommerce/bulk-convert` now returns **201 Created** when every order converts cleanly and **206 Partial Content** when at least one fails — matching the document bulk-upload semantics. Body shape is unchanged (`{data: {converted, errors}}`), so any frontend that already reads counts keeps working; new code can branch on status alone.

- [x] Controller patched in `ECommerceController::bulkConvert`
- [x] Regression test added for the 201 happy path

### ~~Magic-link invite for portal users~~ ✅ Done
New `portal_invite_tokens` table (hashed token + TTL). Invite creation returns `invite_url` carrying the plaintext token; `POST /v1/portal/accept-invite` exchanges it for a Sanctum token and sets the user's password in one step. Token is single-use (7-day TTL).

- [x] Migration `2026_04_19_100000_create_portal_invite_tokens_table.php`
- [x] `PortalInviteToken` model with SHA-256-hashed storage (plaintext never persisted)
- [x] `ClientInvitationService::inviteClientUser` now returns `{user, invite_token, invite_url}`
- [x] `ClientInvitationService::acceptInvite($token, $password)` validates → sets password → marks token used → issues Sanctum token
- [x] `POST /api/v1/portal/accept-invite` (public, `throttle:10,1`) — payload `{token, password, password_confirmation}`
- [x] 5 new regression tests (happy path, invalid token, replay rejection, expiry rejection, invite_url shape)
- [x] `ClientController::invitePortalUser` response now includes top-level `invite_url` alongside `data`

Follow-up landed: `ClientInvitationService` now sends the pre-existing (previously orphaned) `ClientPortalInviteMail` with the magic-link URL instead of the generic onboarding-welcome. Invited clients get the Arabic RTL template at `resources/views/emails/client-portal-invite.blade.php` with a direct "Enter Portal" CTA. Test added asserts the mail is queued with the expected URL.

### ~~Messaging `throttle:10,1` may be too low~~ ✅ Done
Defined a named `messaging` limiter at **30/min per user** (keyed on user id, IP fallback) and swapped the `throttle:10,1` middleware on WhatsApp / SMS endpoints for `throttle:messaging`. 3x headroom over the old ceiling, single knob in `AppServiceProvider` to retune when we see real usage patterns.

- [x] `RateLimiter::for('messaging', ...)` in `AppServiceProvider`
- [x] `messaging/whatsapp` and `messaging/sms` now use `throttle:messaging`

Not converted to per-tenant quota — adds complexity without a clear signal. If end-of-month collection runs hit the ceiling, raise the per-user cap first; per-tenant quota becomes worth it only if we see legitimate users blocking each other.

---

## Done-when-confirmed

Delete sections as they land. Reference commit SHAs here when closing items so the audit trail stays readable.
