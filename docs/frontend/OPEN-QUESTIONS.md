# Open Questions for the Backend Team

Items where the spec doesn't quite answer what the frontend needs to do. Most have a sensible default already wired in the code — the goal of answering these is to lock behavior down before it drifts in production.

Each item lists: **what the frontend currently assumes** → **what we need confirmed**.

**Legend:**
- ✅ **Confirmed** — frontend assumption matches reality
- 🔁 **Correction needed** — frontend should adjust
- 🚫 **Not implemented yet** — backend gap; ETA noted

---

## 01 · Auth & 2FA

### 1.1 Token scope after `requires_2fa: true`
**Doc reference:** `docs/01-authentication.md` → `POST /login` response includes `token` even when `requires_2fa: true`.

**Frontend assumes:** the returned token is **scoped to `/2fa/verify` only** until the user supplies a TOTP. Calling any other authenticated endpoint with it should return 401 or a dedicated `requires_2fa` error. After `/2fa/verify` succeeds, the SAME token is "upgraded" (no new token swap).

**Confirm:**
- Is that the real behavior? (`app/stores/auth.ts` → `verify2fa()` keeps the same token.)
- Or is a new token issued on verify? If so, what is the response shape of `/2fa/verify`?

> 🔁 **Correction needed.** The doc is aspirational — the current implementation does NOT work this way.
>
> - `POST /v1/login` (`app/Domain/Auth/Controllers/AuthController.php:50-67`) does NOT return a `requires_2fa` flag. Response shape is `{ message, data: { user, token } }` only.
> - The issued Sanctum token is **full-access, not scoped**. There are no token abilities applied (`app/Domain/Auth/Services/AuthService.php:98` — `createToken('auth-token')->plainTextToken` with no second arg).
> - 2FA is enforced **downstream** by the `Enforce2fa` middleware (`app/Http/Middleware/Enforce2fa.php:20-40`), which applies only to admin / super-admin users. When they call a protected route without `two_factor_enabled=true`, they get:
>   ```json
>   HTTP/1.1 403
>   { "code": "2fa_required", "message": "...", "setup_url": "/v1/2fa/enable" }
>   ```
>   Non-admin users bypass this entirely — 2FA is not enforced for them.
> - `POST /v1/2fa/verify` (`app/Http/Controllers/Api/V1/TwoFactorController.php:52-63`) returns `{ message: "2FA verified." }` only. **No new token is issued; the same token is kept.** But there's also no DB flag persisted on the session — verification is stateless per request, so the frontend's "promotion" model doesn't match either.
>
> **Frontend action:** key off the 403 + `code: "2fa_required"` response from any protected endpoint rather than reading `requires_2fa` from the login response. Redirect to the 2FA flow on that error.
>
> **Backend TODO (separate PR, tracked elsewhere):** either (a) issue a scoped token on login when 2FA is enabled and swap on verify, or (b) add `requires_2fa` to the login response and update the docs. Current behavior is the (b) gap — docs describe (b), code does neither.

### 1.2 Recovery codes on `/2fa/verify`
**Confirm:** when a user enters a recovery code (not a TOTP), does the same `/2fa/verify` endpoint accept it, or is there a separate path?

> ✅ **Confirmed.** Same endpoint. `TwoFactorService::verify()` (`app/Domain/Auth/Services/TwoFactorService.php:75-94`) tries the TOTP code first, then falls back to recovery-code verification (`verifyRecoveryCode`). A used recovery code is removed from `two_factor_recovery_codes` on success (`TwoFactorService.php:119-121`). No separate route.

### 1.3 `throttle:5,1` on login — per IP or per email?
**Confirm:** the frontend shows a generic "rate limited" toast on 429. Should we also call out "5 attempts / min" in the UX? More specifically, is the limit per IP or per account so we know whether rotating accounts defeats it.

> ✅ **Per IP.** `routes/api.php:136-138` uses `throttle:5,1` with no named limiter, so Laravel's default `ThrottleRequests` middleware keys by `$request->ip()`. Rotating accounts from the same IP is still rate-limited; the same account from different IPs is not. The UX string "5 attempts/min" is accurate.

---

## 02 · Portal user auth

### 2.1 Portal login endpoint
**Doc reference:** `docs/05-clients.md` describes `POST /clients/{client}/invite-portal` which provisions a portal user. `docs/28-client-portal.md` lists `/v1/portal/*` endpoints but does NOT document how portal users obtain their session token.

**Frontend currently has no portal-login path wired up.** 

**Confirm one of:**
- (a) Portal users use the same `POST /login` — the response role flag distinguishes them.
- (b) There's a dedicated `POST /portal/login` endpoint not in the docs.
- (c) The magic link sent in the invite is the only auth mechanism (no password).

If (c), what's the endpoint that exchanges the magic-link token for a Sanctum token?

> ✅ **(a) confirmed — same `/v1/login` endpoint.**
>
> `POST /clients/{client}/invite-portal` (`ClientController::invitePortalUser`, `app/Http/Controllers/Api/V1/ClientController.php:96-107`) creates a User row with:
> - `role = UserRole::Client` (string value `'client'`)
> - `client_id = $client->id`
> - `password = Hash::make(Str::random(16))` — **a random password**, not a magic link
> - `is_active = true`
>
> `ClientInvitationService::inviteClientUser()` (`app/Domain/ClientPortal/Services/ClientInvitationService.php:27-60`) then calls `sendWelcome()`. The welcome email does NOT currently carry a magic-link token — the portal user must use the **standard password-reset flow** to set a password they know, then log in via `POST /v1/login`.
>
> **Frontend action:** route portal users through the same login form. After login, use `user.role === 'client'` to route to `/portal` (see 2.2). Consider displaying "Forgot password?" prominently for clients who just received the invite, since they have no usable password yet.
>
> **Backend follow-up (not blocking):** magic-link auto-login on first invite would be a nicer UX. Not built today.

### 2.2 Portal user role on `/me`
**Confirm:** when a portal user hits `/v1/me`, what does `user.role` return? The frontend's middleware checks `role === 'client'` to route to `/portal`. Is that still the string, or something else (e.g. `client_portal_user` as mentioned in module 28 notes)?

> ✅ **Confirmed: `"client"` (exact string).** `UserRole::Client = 'client'` (`app/Domain/Shared/Enums/UserRole.php:13`), and `/v1/me` returns `role => $user->role->value` (`AuthController.php:89`). The doc-28 reference to `client_portal_user` is inaccurate — frontend's middleware is correct as-is.

---

## 03 · Idempotency-Key

### 3.1 Storage window
**Frontend assumes** `Idempotency-Key` replay window is **24 hours per `(tenant_id, key)` pair**.

**Confirm:** is that right? If shorter (e.g. 1 hour), the offline-queue replay-on-reconnect behavior may need adjustment for mutations that sat pending overnight.

> 🔁 **Partially correct.** TTL is **24 hours** (`app/Http/Middleware/IdempotencyKey.php:24` — `const TTL = 86400`).
>
> BUT the cache key is `idempotency:{key}` only — **NOT scoped by tenant_id** (`IdempotencyKey.php:44`). In practice UUID v4 collisions between tenants are astronomically unlikely (~122 bits of entropy), so this is safe, but the frontend should not assume the key namespace is tenant-scoped. If the frontend ever generated a non-random key (e.g. a hash of a business id), two tenants could collide.
>
> Additional caveats worth noting:
> - **Only GET/HEAD/OPTIONS are skipped** (line 31). All other methods are handled.
> - **Only successful responses (`isSuccessful()`) are cached** (line 73) — 4xx/5xx replies are not stored, so retrying after a server error works normally.
> - **Bodies over 1 MB are not cached** (line 68).
> - **Concurrent requests with the same key**: second request returns HTTP 409 `{ error: 'request_in_progress' }` during the 30-second lock window (line 57-62).
> - **Invalid UUID v4 format**: returns HTTP 422 `{ error: 'Idempotency-Key must be a valid UUID v4' }` (line 40-42). Frontend UUIDs must strictly match v4 pattern.

### 3.2 Response on replay
**Frontend assumes** replaying a key that matches a previously-completed request returns the **original response with the original HTTP status** (e.g. 201 on a successful POST, even on the replay).

**Confirm:** or does the backend return 200 / 409 on replay? This matters because `useQuery`/`useMutation` error handling keys off the status.

> ✅ **Confirmed — original status is preserved.** `IdempotencyKey.php:49` returns `response(decrypt($cached['body']), $cached['status'])` with the cached status code. The replay also gets an extra response header: **`X-Idempotency-Replay: true`** — the frontend can key off this to distinguish a fresh write from a replay if desired (useful for toasts: "Order already submitted" vs "Order submitted").

### 3.3 Scope
**Confirm:** is `Idempotency-Key` required, optional, or opportunistic on each of these endpoints?
- `POST /invoices` · `POST /invoices/{id}/send` · `POST /payments`
- `POST /bills` · `POST /bills/{id}/payments`
- `POST /payroll/{id}/calculate|approve|mark-paid`
- `POST /eta/documents/{invoice}/submit|cancel`
- `POST /subscription/subscribe|change-plan|renew`
- `POST /import`

The frontend currently **generates a UUID per mutation** on these routes. If the backend rejects requests without one, we're fine. If it silently ignores them, retries can double-charge.

> 🔁 **Major gap — most listed endpoints do NOT have idempotency wired.**
>
> Only **2** endpoints currently have the `idempotent` middleware (confirmed by grepping `routes/api.php`):
> - ✅ `POST /subscription/subscribe` (line 269)
> - ✅ `POST /subscription/renew` (line 271)
>
> **Not wired** (middleware silently does nothing — the key is ignored):
> - ❌ `POST /invoices`, `POST /invoices/{id}/send`
> - ❌ `POST /payments`
> - ❌ `POST /bills`, `POST /bills/{id}/payments`
> - ❌ `POST /payroll/{id}/calculate|approve|mark-paid`
> - ❌ `POST /eta/documents/{invoice}/submit|cancel`
> - ❌ `POST /subscription/change-plan`
> - ❌ `POST /import`
>
> Scope behavior: **optional** everywhere (the middleware does `if (! $key) return $next($request);` at line 37). It never rejects a missing key.
>
> **Frontend risk today:** on the non-wired endpoints, a retry after a dropped connection CAN create duplicates — e.g., double invoices, double payments. The frontend's UUID generation is currently "insurance that does nothing" on those routes.
>
> **Backend TODO (I'll file a separate task):** extend the `idempotent` middleware to all the listed financial-write endpoints. Payment and invoice creation are the highest priority. Not blocking frontend work — frontend can keep generating UUIDs; they'll start taking effect as endpoints are patched.

---

## 04 · Response envelopes

### 4.1 Bulk endpoint status codes
**Doc reference:** `docs/21-documents.md` → bulk upload returns 201 on full success, 206 on partial.
**Doc reference:** `docs/22-ecommerce.md` → bulk-convert returns 200 always with per-order status in the body.

**Confirm:** which pattern is canonical? The frontend handles both today but we'd like to pick one.

> 🔁 **Both patterns coexist today — no canonical choice yet.**
>
> - **Document bulk upload** (`POST /documents/bulk`, `DocumentController.php:59-84`): returns **201** on full success, **206 (Partial Content)** when any file fails. Per-file errors in response body.
> - **E-commerce bulk-convert** (`ECommerceController.php:112-125`): returns **200** always, with `converted` / `errors` counts in the body. No 206 path.
>
> **Proposed canonical (backend decision):** adopt the 201/206 pattern everywhere new, since 206 gives the frontend a clean "show a partial-success UI" signal without parsing the body. Will file a follow-up to align the e-commerce endpoint.
>
> **Frontend action until then:** keep handling both. Treat `206` as partial-success when returned. When `200`, inspect `errors` count in the body.

### 4.2 Error shape on 422
**Doc reference:** every doc says 422 is `{ message, errors: { field: [msgs] } }`.

**Confirm:** the `errors` object is keyed by **HTML form field name** (e.g. `"lines.0.quantity"` for a nested array), not a human label. `useZodForm.applyApiErrors()` assumes that shape.

> ✅ **Confirmed — dot-notation keys for nested fields.** Laravel's standard `ValidationException::errors()` output is preserved by `ApiResponse::validationError()` (`app/Http/ApiResponse.php:68-76`) and wired in `bootstrap/app.php:100-107`.
>
> Exact shape:
> ```json
> HTTP/1.1 422
> {
>   "error": "validation_error",
>   "message": "The given data was invalid.",
>   "errors": {
>     "lines.0.quantity": ["The lines.0 quantity must be a number."],
>     "email": ["The email has already been taken."]
>   }
> }
> ```
>
> Note the extra top-level `"error": "validation_error"` slug — frontend's `applyApiErrors()` only needs `errors`, but if any code branches on the top-level error slug, `validation_error` is what to match.

---

## 05 · Rate limiting classes

### 5.1 `throttle:reports` and `throttle:exports`
**Doc reference:** `docs/19-reporting.md` says these are custom rate-limit classes.

**Confirm:** what are the numeric limits? (e.g. `throttle:reports` = N requests per minute per user?) The frontend could preemptively disable buttons during cooldown if we knew the window.

> ✅ **Confirmed limits** (`app/Providers/AppServiceProvider.php:148-158`):
>
> | Limiter | Rate | Keyed by |
> |---|---|---|
> | `throttle:reports` | **10 / min** | authenticated user id, falls back to IP |
> | `throttle:exports` | **5 / min** | authenticated user id, falls back to IP |
> | `throttle:imports` | **3 / min** | authenticated user id, falls back to IP |
>
> All three key on `$request->user()?->id ?: $request->ip()`. For an authenticated user across multiple tabs, the limit is shared. Frontend can safely show cooldown based on a client-side counter synced to the window.

### 5.2 Messaging `throttle:10,1`
**Confirm:** per user, per tenant, or per IP? 10 sends / min is very low if it's per tenant for a busy accounting firm.

> ✅ **Per authenticated user.** `throttle:10,1` is Laravel's numeric form — it keys by `$request->user()?->getAuthIdentifier() ?? $request->ip()` automatically. Since the messaging endpoints are behind `auth:sanctum`, this resolves to the user id.
>
> If 10/min/user is genuinely too low for accounting firms, raise it with backend separately — the cap will not scale with seats today.

---

## 06 · Multi-tenancy

### 6.1 Tenant inference
**Doc reference:** `docs/01-authentication.md` → "Tenant context is inferred from the authenticated user — there is no explicit `X-Tenant-ID` header for app users."

**Confirm:** this is consistent across every endpoint in modules 03–27? The frontend has dropped `X-Tenant` entirely.

> 🔁 **Docs are simplifying — `X-Tenant` IS accepted.** `IdentifyTenant` middleware (`app/Http/Middleware/IdentifyTenant.php`) resolves in priority order:
> 1. `X-Tenant` header (numeric id OR slug)
> 2. Subdomain on host (≥3 parts)
> 3. Route parameter `{tenant}`
> 4. Authenticated user's own `tenant_id` (fallback added during this review — previously 404'd without X-Tenant)
>
> **Important guard:** if the user is not a super-admin and the resolved tenant doesn't match their own `tenant_id`, the request is rejected with 403. So the header cannot be used to cross tenants for regular users — only super-admins can override via header.
>
> **Frontend action:** keeping `X-Tenant` dropped is fine for the app — user's `tenant_id` resolves correctly as step 4. **Don't set `X-Tenant`** on regular app user requests; if you did and the header resolved to a different tenant than the user's, the request would 403. Only useful inside the super-admin panel, which has its own flow.
>
> ⚠ **Historical note:** before the 2026-04-19 release the middleware had no priority-4 fallback. If you're running an older backend, keep sending `X-Tenant` on every authenticated request until the fix is deployed.

### 6.2 Cross-tenant users
**Confirm:** can a single user belong to multiple tenants (e.g. an accountant serving several firms)? If yes, how do they switch tenants? There's no such UI today.

> ✅ **One tenant per user — confirmed, no switch UI needed.** `users` has a single `tenant_id` FK (`database/migrations/0001_01_01_000001_create_users_table.php:16`), no pivot table, no `switchTenant()` method on the model. A user serving multiple firms would need one account per tenant today. Out of scope for current sprint.

---

## 07 · Webhooks (inbound)

### 7.1 Signature verification
**Doc reference:** `docs/02-public-routes.md` lists `POST /webhooks/paymob|fawry|beon-chat|ecommerce/{platform}` as signature-verified.

**Frontend is not a consumer of these** — they come INTO the backend. Flagged only so we know there's no work needed on our side. Delete this section after confirming.

> ✅ **No frontend work.** Verified:
> - Paymob: HMAC-SHA512 in `PaymobService::verifyHmac()`
> - Fawry: SHA-256 in `FawryService::handleCallback()`
> - Beon-chat: HMAC-SHA256, signature header check inline in `routes/api.php:177-187`
> - E-commerce: ⚠ currently NO signature verification in `ECommerceService::handleIncoming()` — logged as a backend TODO, but frontend is unaffected.
>
> Section can be deleted.

---

## 08 · Storage & file limits

### 8.1 Document upload
**Doc reference:** `docs/21-documents.md` — single: 20 MB max, bulk: 10 files max.

**Confirm:** per-file 20 MB in bulk, or 20 MB aggregate?

> ✅ **Per-file 20 MB in bulk.** `BulkUploadDocumentRequest.php:22-23`:
> ```php
> 'files' => ['required', 'array', 'min:1', 'max:10'],
> 'files.*' => ['file', 'max:20480', /* mimes */],
> ```
> Max 10 files, each up to 20 MB. Theoretical max per request = 200 MB. If server-side nginx/PHP limits are tighter, those hit first — check with backend if you want an aggregate client-side cap.

### 8.2 Receipt upload on expenses
**Doc reference:** `docs/10-expenses.md` — "jpg/png/pdf ≤ 10MB".

**Confirm:** hard reject at 10MB or soft warning? Frontend currently has no client-side size check — trusting backend 413.

> ✅ **Hard reject via 422, not 413.** `StoreExpenseRequest.php:38`:
> ```php
> 'receipt' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:10240'],
> ```
> Laravel's `max:10240` on a `file` rule returns a 422 validation error (`receipt: ["The receipt field must not be greater than 10240 kilobytes."]`), not a 413. MIME allowlist is strict: **jpg/png/pdf only** — no webp, heic, or other image formats. Consider adding a client-side check + human-friendly error for the 10 MB limit and for unsupported file types.

---

## 09 · Feature-flag source of truth

### 9.1 `tenant.features[]` on `/me`
**Frontend assumes** `tenant.features[]` is the **single source of truth** for plan gating. The array is the union of: plan-bundled flags + any per-tenant overrides.

**Confirm:** that's the behavior? No separate "flags" endpoint to merge?

> 🚫 **Not implemented yet.** `/v1/me` currently returns `{ user, tenant: { id, name, slug, email, phone, logo_path, tagline, primary_color, secondary_color, city } }` — NO `features` field (`AuthController.php:98-109`).
>
> The machinery exists:
> - `Plan.features` JSON column with catalog keys + booleans (seeded by `PlanSeeder.php`)
> - `FeatureFlag` model with a 4-tier override hierarchy (disabled_for_tenants → enabled_for_tenants → enabled_for_plans → is_enabled_globally → rollout_percentage)
> - `FeatureFlagService::getAllForTenant($tenantId, $planId)` computes the merged map
>
> But nothing is wired into `/me`. Today the frontend must fetch `/v1/subscription` to get `plan.features` and apply per-tenant overrides client-side (which it can't do — overrides aren't exposed).
>
> **Backend TODO (short):** add `'features' => FeatureFlagService::getAllForTenant(...)` into the `/me` response. ETA: next backend sprint. Until shipped, frontend should continue reading from `/v1/subscription` and ignore per-tenant overrides. Follow up on this before feature-gated UI goes to production.

### 9.2 Flag naming
**Confirm** these 17 flag slugs are canonical:
```
accounting, audit_log, bills_vendors, budgeting, clients, client_portal,
collections, cost_centers, custom_reports, documents, e_invoice, expenses,
fixed_assets, inventory, invoicing, payroll, reports, tax, timesheets
```
(stored at `app/core/subscription/flags.ts`)

Any flag added server-side that ISN'T in this list will render its feature's sidebar entry invisible on the frontend until we extend the const.

> 🔁 **Frontend is missing 4 backend flags.** Canonical list at `config/features.php:19-165`:
>
> ```
> accounting, api_access, audit_log, banking, bills_vendors, budgeting,
> client_portal, clients, collections, cost_centers, custom_reports, documents,
> e_invoice, ecommerce, expenses, fixed_assets, inventory, invoicing, payroll,
> priority_support, reports, tax, timesheets
> ```
> Total: **23 slugs.**
>
> Frontend list is missing:
> - `api_access` (addon — exposed API for integrations)
> - `banking` (bank reconciliation module)
> - `ecommerce` (e-commerce integration module)
> - `priority_support` (addon)
>
> **Frontend action:** add these four to `app/core/subscription/flags.ts`. Even if no UI gates on them today, the const should stay in sync with the catalog or future plan changes will be invisible.

---

## 10 · Permissions

### 10.1 `permissions[]` on `/me`
**Confirm** these 30 slugs are the complete set:
```
view_dashboard, view_audit, manage_subscription, manage_team, manage_onboarding,
manage_settings, manage_landing_page, manage_clients, invite_client_portal,
manage_accounts, manage_journal_entries, post_journal_entries, manage_invoices,
send_invoices, manage_payments, manage_collections, manage_vendors, manage_bills,
manage_expenses, manage_fixed_assets, manage_inventory, manage_cost_centers,
manage_tax, manage_eta, manage_employees, manage_payroll, manage_timesheets,
approve_timesheets, manage_engagements, view_reports, manage_reports,
manage_approvals, manage_alerts, manage_documents, manage_integrations
```
(stored at `app/core/rbac/permissions.ts`)

> 🔁 **Frontend has 4 permissions that don't exist in backend.** Canonical list at `config/permissions.php:13-76` is **31 slugs**:
>
> ```
> approve_timesheets, invite_client_portal, manage_accounts, manage_bills,
> manage_clients, manage_collections, manage_cost_centers, manage_documents,
> manage_employees, manage_eta, manage_expenses, manage_fixed_assets,
> manage_integrations, manage_inventory, manage_invoices, manage_journal_entries,
> manage_landing_page, manage_onboarding, manage_payments, manage_payroll,
> manage_settings, manage_subscription, manage_tax, manage_team, manage_timesheets,
> manage_vendors, post_journal_entries, send_invoices, view_audit, view_dashboard,
> view_reports
> ```
>
> **Not in backend (remove from frontend or add to backend):**
> - `manage_engagements`
> - `manage_reports`
> - `manage_approvals`
> - `manage_alerts`
>
> Pick a direction per slug:
> - `manage_approvals` / `manage_alerts` — backend has features but no dedicated permission; currently gated by `view_dashboard` or role. Worth adding backend permissions for fine-grained RBAC.
> - `manage_reports` — redundant with `view_reports` today (reports are read-only for users); keep or drop depending on whether report templates/schedules become user-editable.
> - `manage_engagements` — engagements module exists; permission missing. Likely a backend oversight — add.

### 10.1b `permissions[]` derivation
**Frontend assumes** the array on `/me` is the user's full effective permission set.

> ✅ **Confirmed.** Built by `PermissionService::getUserPermissions()` (`app/Domain/Auth/Services/PermissionService.php:22-38`):
> - Super-admins get the complete catalog.
> - Others: Spatie DB (`getAllPermissions()->pluck('name')`) if roles are assigned, else falls back to `config("permissions.{$role->value}")` by the user's enum role.
>
> No need to merge anything client-side. Treat `permissions[]` as authoritative.

### 10.2 Role-to-permission map
**Confirm:** is there a backend-visible mapping of **built-in role → default permissions** the frontend could display in team management? Today the team page shows permission slugs as-is, not role presets.

> ✅ **Yes, presets exist but aren't exposed via API.** Defined in `config/permissions.php` and seeded via `RolesSeeder.php:24-43`:
>
> | Role (Spatie name) | Permissions |
> |---|---|
> | `tenant_admin` | all 31 |
> | `tenant_accountant` | ~20 (financial + clients + docs, no team/billing/audit/landing) |
> | `tenant_auditor` | 3 (view_dashboard, manage_documents, view_reports) |
> | `tenant_limited` | 2 (manage_clients, manage_documents) |
>
> 🚫 **Not exposed through any endpoint today.** If the frontend wants a "Apply Accountant preset" button in team management, backend needs a new endpoint like `GET /v1/rbac/role-presets`. File a ticket if this UX is on the roadmap; otherwise frontend can hardcode the map with the caveat that it may drift.

---

## Template for answering

Please edit this file in place and mark each item:

- ✅ **Confirmed** — frontend assumption matches reality
- 🔁 **Correction needed** — (explain) — I'll update the frontend
- 🚫 **Not implemented yet** — (eta) — frontend will stub/skip until ready

And delete the section once all its items are resolved.
