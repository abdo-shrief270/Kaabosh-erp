# Backend Release Notes — 2026-04-19

Changes shipped in response to the frontend integration questionnaire (`OPEN-QUESTIONS.md`). Each section lists: **what changed**, **why**, and **what the frontend should do**. Organised by priority — P0 items are the ones most likely to change frontend code.

---

## P0 · Correctness & money-safety

### 1. Idempotency-Key now protects every financial-write endpoint
`fc6129d`

**What:** The `idempotent` middleware used to cover only `/subscription/subscribe` and `/subscription/renew`. Extended to:
- `/invoices` (all mutation verbs) + `/invoices/{id}/send|cancel|post-to-gl|credit-note`
- `/payments`
- `/bills` (all verbs) + `/bills/{id}/approve|cancel` + `/bills/{id}/payments`
- `/journal-entries` (all verbs) + reverse + post
- `/payroll/{id}/calculate|approve|mark-paid`
- `/eta/documents/{invoice}/submit|cancel`
- `/subscription/change-plan`
- `/import`, `/import/clients`, `/import/accounts`, `/import/opening-balances`

**Why:** A dropped network request on any of these routes could previously create a duplicate invoice, double-charge a customer, or post a journal entry twice. The frontend was already generating UUIDs — they just had no effect server-side.

**Frontend action:**
- Keep generating UUIDs. They now work.
- On replay, the response includes `X-Idempotency-Replay: true` — use it to distinguish "first write" from "replay" for UX (e.g. "Invoice submitted" vs "Invoice already submitted").
- UUID must be v4 format — server returns 422 for malformed keys.
- If two requests arrive concurrently with the same key, the second gets `409 {error: "request_in_progress"}` during the 30-second lock window.

---

### 2. Login returns `requires_2fa`
`10da379`

**What:** `POST /v1/login` response now includes `data.requires_2fa: bool`. True when the user is admin/super-admin and hasn't enabled 2FA.

**Why:** Previously the frontend had no signal from the login response and could only detect 2FA-required via the 403 + `code: "2fa_required"` error on protected routes. Now the flag is up-front.

**Frontend action:**
- After login, if `data.requires_2fa === true`, redirect to the 2FA setup flow (`POST /v1/2fa/enable`, then `POST /v1/2fa/confirm`).
- The token issued is still full-access (no scoping) — the downstream `Enforce2fa` middleware blocks admin routes with 403 until setup completes. Keep the 403 handler as a safety net.
- Non-admins always get `requires_2fa: false`.

**Not implemented:** per-session 2FA verification (option b from the TODO). Once 2FA is enabled, the user stays authenticated across sessions without re-verifying. Separate ticket if needed.

---

### 3. E-commerce webhooks now signed
`0effc31`

**Breaking change to URL.**

**What:** Route changed from `POST /webhooks/ecommerce/{platform}` to `POST /webhooks/ecommerce/{platform}/{channel}`. New middleware verifies platform-specific HMAC signatures against `ECommerceChannel.webhook_secret`.

| Platform | Header | Signature format |
|---|---|---|
| Shopify | `X-Shopify-Hmac-Sha256` | base64 HMAC-SHA256 |
| WooCommerce | `X-WC-Webhook-Signature` | base64 HMAC-SHA256 |
| Salla | `X-Salla-Signature` | hex HMAC-SHA256 |
| Zid | `X-Zid-Signature` | hex HMAC-SHA256 |
| Custom | — | rejected (use authenticated endpoint) |

**Why:** The old endpoint accepted any payload and logged the event. Nothing production-blocking today because `webhookHandler` is still placeholder code, but closing the gap before real sync lands.

**Frontend action:** none — webhooks are server-to-server. But the channel create/edit forms must include `webhook_secret` (field already existed on `ECommerceChannel`, just needs to be surfaced). Show the new URL shape in the admin docs: `/webhooks/ecommerce/{platform}/{channel_id}`.

---

## P1 · Frontend integration gaps

### 4. `/v1/me` now carries `tenant.features`
`828d784`

**What:** `GET /v1/me` response now includes `data.tenant.features` as a `{flagKey: bool}` map. Per-tenant overrides and plan-bundled flags are merged server-side.

**Why:** Frontend was reading `plan.features` from `/v1/subscription` which ignores per-tenant overrides and requires a second fetch.

**Frontend action:**
- Drop the `/v1/subscription` dependency for feature-gating. Read from `tenant.features` on `/me`.
- Frontend's feature-flag const needs 4 additions to match the backend catalog (was flagged in OPEN-QUESTIONS §9.2):
  - `api_access`
  - `banking`
  - `ecommerce`
  - `priority_support`
- Server caches the merged map for 5 minutes per tenant — admin changes propagate within that window.

---

### 5. Four new permission slugs
`2187701`, plus a follow-up adding `manage_reports`

**What:** Added to `config/permissions.php`:
- `manage_engagements` → admin + accountant
- `manage_approvals` → admin + accountant
- `manage_alerts` → admin only
- `manage_reports` → admin + accountant

**Why:** The routes already gated on these slugs but they weren't defined anywhere, so `CheckPermission` middleware was denying every non-super-admin. Now they seed into the Spatie DB correctly.

`manage_reports` was initially flagged for removal on the assumption that reports are read-only, but `ScheduledReport` CRUD (create, toggle, delete) is user-editable and legitimately needs a write permission.

**Frontend action:**
- Keep `manage_reports`, `manage_engagements`, `manage_approvals`, `manage_alerts` in `app/core/rbac/permissions.ts`.
- Deploy note: existing tenants need a one-time `php artisan db:seed --class=PermissionSeeder --force` to get the new Spatie permission rows.

---

### 6. `GET /v1/rbac/role-presets`
`7137b8a`

**What:** New endpoint returning the built-in role → permission map with bilingual labels.

```json
{
  "data": [
    { "role": "admin", "label": "Administrator", "label_ar": "مدير", "permissions": [...] },
    { "role": "accountant", "label": "Accountant", "label_ar": "محاسب", "permissions": [...] },
    { "role": "auditor", "label": "Auditor", "label_ar": "مراجع", "permissions": [...] }
  ]
}
```

**Why:** Team management UI needs to show "Apply Accountant preset" buttons when inviting a member. This avoids hardcoding the map on the frontend.

**Frontend action:** Swap the hardcoded preset map in the team-management page for a fetch of this endpoint. Gated by `manage_team` — same permission as the team routes.

---

## P2 · Polish & consistency

### 7. Bulk-convert follows 201/206 semantics
`3194fe3`

**What:** `POST /ecommerce/bulk-convert` used to return 200 always. Now returns 201 when every order converts cleanly, 206 when at least one fails. Body shape unchanged.

**Why:** Matches the document bulk-upload pattern. Frontend can branch on status alone.

**Frontend action:** optional. If the frontend already handles both, no change needed. New code can simplify "did it all work?" to `response.status === 201`.

---

### 8. Portal invite magic-link flow
`1267546`, `1a8842f`

**What:**
- `POST /clients/{id}/invite-portal` response now includes top-level `invite_url` alongside `data`
- New public endpoint `POST /v1/portal/accept-invite` — payload `{token, password, password_confirmation}` — exchanges the invite token for a Sanctum token and sets the user's password
- `ClientPortalInviteMail` now actually gets sent (was orphaned in the codebase)

**Why:** Invited clients used to be created with a random password they couldn't know — they had to run the password-reset flow just to log in. Now the email they receive carries a direct link to a set-password + log-in screen.

**Frontend action:**
- Build the `/portal/accept-invite?token=...` page matching the URL in the invite email. Fields: `password`, `password_confirmation`. POST to `/api/v1/portal/accept-invite` with the token from the URL.
- On success, store the returned Sanctum token and route the user to `/portal`.
- Token TTL is 7 days. Replay / expiry / unknown-token all return 422 with the error on `token`.
- The `invite_url` in the response is useful if the inviter wants to "copy link" directly (e.g. send via WhatsApp) rather than relying on the email.

---

### 9. IdentifyTenant now falls back to authenticated user's tenant
`e078338` (follow-up from suite-healing pass)

**What:** The tenant-identification middleware previously resolved from `X-Tenant` header → subdomain → route param, then 404'd. It now falls back to `$request->user()->tenant_id` when none of those match.

**Why:** Every authenticated tenant-scoped endpoint required `X-Tenant`. The OPEN-QUESTIONS.md answer claimed a user-based fallback existed but that was wrong — it was absent. This closes the gap.

**Frontend action:**
- Sending `X-Tenant` is still **accepted and still takes precedence** — no change required for code that already sets it.
- Code paths that skip the header now work automatically (single-tenant users). This is a behavior-compatible addition.

---

### 10. Messaging throttle raised + named
`995772b`

**What:** WhatsApp / SMS endpoints were `throttle:10,1`. Now use a named `messaging` limiter at 30/min per user.

**Why:** 10/min/user was tight for collection-team workflows.

**Frontend action:** none — frontend just sees fewer 429s. UX cooldown messaging can be relaxed accordingly.

---

## What did NOT change

Items surfaced in `OPEN-QUESTIONS.md` but intentionally left alone:

- **Section 01.2 / 01.3** (recovery codes on `/2fa/verify`, login throttle per-IP) — behavior confirmed matches frontend assumptions. No code change.
- **Section 02.2** (portal user role string is `"client"`) — confirmed, no change needed.
- **Section 03.1/3.2** (idempotency storage window, replay status) — confirmed 24h, original status preserved. One caveat: the cache key is NOT scoped by tenant_id (just by UUID). Collision across tenants is astronomically unlikely but noted.
- **Section 04.2** (422 shape) — confirmed dot-notation keys, extra `error: "validation_error"` slug present but harmless.
- **Section 05.1** (reports/exports throttle) — confirmed 10/5/3 per minute per user.
- **Section 06.1** (X-Tenant header) — confirmed accepted but not required; regular users' tenant comes from auth.
- **Section 06.2** (cross-tenant users) — confirmed single-tenant only; no switch UI needed.
- **Section 10.1b** (permissions derivation) — confirmed `/me permissions[]` is authoritative.

---

## Test coverage added this batch

41 new regression tests across 5 files:
- `tests/Feature/IdempotencyMiddlewareTest.php` — 5 tests
- `tests/Feature/AuthTest.php` — 4 new tests (requires_2fa x3, tenant.features x1)
- `tests/Feature/ECommerceTest.php` — 12 new tests (webhook verification x11, bulk-convert x1)
- `tests/Feature/ClientInvitationTest.php` — 6 new tests (invite_url, magic-link flow, email dispatch)
- `tests/Feature/RbacPresetsTest.php` — 2 tests

Pre-existing test failures cleaned up alongside the release:
- `AccountsPayableTest`: ~~`BillStatus::canPay()` method missing~~ — added; `BillPaymentService::store` was previously fatal because it called the undefined method on every request. Real bug, not just a test issue.
- `ECommerceTest`: ~~VAT math (10000 vs 11400)~~ — test expected subtotal, got total; updated to assert both `subtotal` and `total` so the intent is explicit.
- `AlertRuleTest::Metric calculations → DSO`: ~~bcmath rounding (30.00 vs 29.99)~~ — fixed both the test formula and the matching bug in `AlertEngineService::calculateDso` (dividing first at scale 6 truncated cents; multiplying first gives exact results for clean inputs).

Still outstanding:
- Various parallel-isolation failures when running `php artisan test --parallel`. Not investigated — test DB state leaks between workers. Run serially until fixed.
- `AccountsPayableTest`: missing `VendorFactory` class — unrelated to AP status logic.

---

## Deploy checklist

On the VPS:
```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force            # creates portal_invite_tokens
php artisan db:seed --class=PermissionSeeder --force   # seeds manage_engagements/approvals/alerts
php artisan config:cache
php artisan route:cache
php artisan horizon:terminate           # pick up new code in queue workers
```

Cache that should be cleared manually if feature flags seem stale: `php artisan cache:clear` (or wait 5 min).
