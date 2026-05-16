# New & Discovered API Routes — 2026-04-19

Routes that were either **newly added this batch** or **existed as controllers but never wired** (so they 404'd in production). All are now live on the API.

Every authenticated route below lives behind:
- `Authenticate:sanctum`
- `IdentifyTenant` (falls back to the caller's `tenant_id` — no need to set `X-Tenant` unless you're a super-admin operating cross-tenant)
- `EnsureActiveUser`
- `Enforce2fa` (admin / super-admin only — returns 403 `{ code: "2fa_required" }` until 2FA is set up)

Unless noted, 422 responses follow the usual shape `{ error: "validation_error", message, errors: { "field": ["..."] } }`.

---

## 1 · Portal — Accept Invite (NEW)

`POST /api/v1/portal/accept-invite`  *(public, rate-limited)*

Exchanges the single-use invite token emailed to a client for a Sanctum token and sets their password in one step. Replaces the "log in with a random password, then reset" detour.

**Middleware:** `throttle:10,1`. No auth required — the token is the credential.

**Request body:**
```json
{
  "token": "plaintext-64-char-token-from-email",
  "password": "min-8-chars",
  "password_confirmation": "..."
}
```

**Response 200:**
```json
{
  "message": "Invite accepted.",
  "data": {
    "user": {
      "id": 42,
      "name": "Client Name",
      "email": "client@example.com",
      "role": "client",
      "tenant_id": 7,
      "client_id": 3
    },
    "token": "1|sanctum-bearer-token"
  }
}
```

**Errors:**
- `422 { errors: { token: ["Invite is invalid or has expired."] } }` for unknown / already-used / expired tokens.

**Invite URL shape:** the invite email (and the invite API response — see below) carries a URL like `{frontend_url}/portal/accept-invite?token=<token>`. Parse the `token` from the query string and POST it to this endpoint.

---

## 2 · Invite-Portal Response Now Includes `invite_url` (SHAPE CHANGE)

`POST /api/v1/clients/{client}/invite-portal`

The existing endpoint's response gained a top-level `invite_url` field alongside `data`.

**Before:**
```json
{ "data": { "id": 42, "email": "...", "role": "client", ... } }
```

**After:**
```json
{
  "data": { "id": 42, "email": "...", "role": "client", ... },
  "invite_url": "https://app.muhasebi.com/portal/accept-invite?token=..."
}
```

Use `invite_url` if the accountant wants to share the link directly (e.g. copy-to-clipboard / WhatsApp) without waiting for email delivery.

---

## 3 · Portal — Invoice Disputes (WIRED)

`GET|POST /api/v1/portal/disputes`, `GET /api/v1/portal/disputes/{dispute}`

Portal auth required (`role === "client"`). The controller existed but had no routes.

### `GET /portal/disputes`
Lists the authenticated client's disputes.
**Response:** `{ "data": [ InvoiceDispute, ... ] }`

### `POST /portal/disputes`
Creates a new dispute.
**Request:**
```json
{
  "invoice_id": 123,            // required, must belong to the authenticated client's tenant
  "subject": "Incorrect amount",
  "description": "The amount doesn't match our agreement.",
  "priority": "high"            // optional: low|medium|high (default low)
}
```
**Response 201:** `{ "data": InvoiceDispute }` with `status: "open"`.

### `GET /portal/disputes/{dispute}`
Returns a single dispute with `invoice` + `resolver` eager-loaded.
**Response:** `{ "data": InvoiceDispute }`. Returns 403 if the dispute doesn't belong to the caller.

---

## 4 · Portal — Payment Plans (WIRED)

`GET /api/v1/portal/payment-plans`, `POST /api/v1/portal/invoices/{invoice}/payment-plan`, `POST /api/v1/portal/installments/{installment}/pay`

### `GET /portal/payment-plans`
Lists the client's plans.
**Response:** `{ "data": [ PaymentPlan, ... ] }`

### `POST /portal/invoices/{invoice}/payment-plan`
Sets up a payment plan against an invoice.
**Request:**
```json
{
  "installments": 5,             // required int 2–60
  "frequency": "monthly"         // required: weekly|biweekly|monthly
}
```
**Response 201:** `{ "data": PaymentPlan }` with `installments_count`, `total_amount`, `installment_amount` computed.

### `POST /portal/installments/{installment}/pay`
Records a payment against a specific installment.
**Request body:** passthrough — service accepts gateway-specific details (`payment_method`, `transaction_id`, etc.).
**Response 200:** `{ "data": { installment, plan } }`. Plan's status flips to `completed` once every installment is paid.

---

## 5 · Portal — Client Report Summary (WIRED)

`GET /api/v1/portal/reports`

Dashboard aggregate for the client's own book.

**Response 200:**
```json
{
  "data": {
    "outstanding_balance": "12500.00",
    "aging": { "0_30": "5000.00", "31_60": "2500.00", "61_90": "0.00", "over_90": "5000.00" },
    "recent_invoices": [ ... ],
    "recent_payments": [ ... ]
  }
}
```

---

## 6 · RBAC — Role Presets (NEW)

`GET /api/v1/rbac/role-presets`

Built-in role → permission map, used to seed the "Apply preset" dropdown in team management.

**Middleware:** `permission:manage_team`

**Response 200:**
```json
{
  "data": [
    { "role": "admin",      "label": "Administrator", "label_ar": "مدير",   "permissions": ["view_dashboard", "manage_team", ...] },
    { "role": "accountant", "label": "Accountant",    "label_ar": "محاسب",  "permissions": ["view_dashboard", "manage_invoices", ...] },
    { "role": "auditor",    "label": "Auditor",       "label_ar": "مراجع",  "permissions": ["view_dashboard", "manage_documents", "view_reports"] }
  ]
}
```

No pagination — the list is small and static per config.

---

## 7 · E-commerce Webhook URL (BREAKING)

`POST /api/v1/webhooks/ecommerce/{platform}/{channel}`

Previously `/webhooks/ecommerce/{platform}` — **added a required `{channel}` path segment** because the old shape couldn't identify which tenant's channel the webhook belonged to. Every platform's webhook URL must be reconfigured to include the channel id.

**Middleware:** `ecommerce.verify` — signature is verified against `ECommerceChannel.webhook_secret` using the platform-specific scheme.

| Platform | Header | Format |
|---|---|---|
| shopify | `X-Shopify-Hmac-Sha256` | base64 HMAC-SHA256 |
| woocommerce | `X-WC-Webhook-Signature` | base64 HMAC-SHA256 |
| salla | `X-Salla-Signature` | hex HMAC-SHA256 |
| zid | `X-Zid-Signature` | hex HMAC-SHA256 |
| custom | — | rejected; use an authenticated endpoint |

**Failure modes (all 401 JSON):**
- `{ error: "invalid_channel" }` — channel id doesn't exist, is inactive, or its platform doesn't match the URL
- `{ error: "webhook_secret_not_configured" }` — channel row has no `webhook_secret`
- `{ error: "invalid_signature" }` — missing or wrong HMAC

**Success 200:** `{ "handled": true, "event": "order.created" }` (or `{ handled: false }` for unknown event types).

**Frontend impact:** on the channel create/edit form, make `webhook_secret` visible/required. Surface the full URL shape (including channel id) in the admin docs so operators configure the right URL at each platform.

---

## 8 · Statement Builder — Templates & Generation (WIRED)

All under `/api/v1/statement-templates/*`. Controller existed, routes were never added.

**Middleware on the group:** `feature:reports`, `permission:view_reports`
**Write operations (POST/PUT/DELETE):** additionally gated by `permission:manage_reports`

### `GET /statement-templates?type=X&per_page=15`
Paginated list. `type` optional filter: `income_statement | balance_sheet | cash_flow`.
**Response:** standard Laravel paginator — `{ data: [...], links, meta }`.

### `POST /statement-templates`
Creates a template. Structure is a JSON definition of sections + account selection + calculated formulas.
**Request:**
```json
{
  "name_ar": "قائمة الدخل",
  "name_en": "Income Statement",
  "type": "income_statement",
  "is_default": false,
  "structure": {
    "sections": [
      {
        "id": "revenue",
        "label_ar": "الإيرادات",
        "label_en": "Revenue",
        "accounts": { "type": "revenue" },
        "subtotal": true
      },
      {
        "id": "cogs",
        "label_ar": "تكلفة المبيعات",
        "accounts": { "codes_from": "5100", "codes_to": "5199" },
        "subtotal": true,
        "negate": true
      },
      {
        "id": "gross_profit",
        "label_ar": "مجمل الربح",
        "is_calculated": true,
        "formula": "revenue - cogs",
        "subtotal": true
      }
    ]
  }
}
```
**Response 201:** `{ "data": StatementTemplate, "message": "Statement template created." }`

**Section account-selection modes (pick one per section):**
- `accounts.type`: `asset | liability | equity | revenue | expense` — picks all accounts of that type
- `accounts.codes_from` + `accounts.codes_to`: inclusive numeric code range (e.g. `"5100"` → `"5199"`)
- `accounts.ids`: explicit list of account ids
- `is_calculated` + `formula`: no accounts; computed from previous sections' ids (simple `a + b - c` arithmetic)

### `GET /statement-templates/{id}`
Single template, creator eager-loaded.

### `PUT /statement-templates/{id}` — same body as POST.

### `DELETE /statement-templates/{id}` — soft delete.

### `GET /statement-templates/{id}/generate?from=YYYY-MM-DD&to=YYYY-MM-DD&compare_from=...&compare_to=...`
Produces the generated statement for the given period. `compare_from/to` optional for period-over-period comparison.
**Response:** `{ "data": { sections: [...], total, currency } }` — exact shape depends on template.

### `GET /statement-templates/ratios?from=...&to=...`
Returns common financial ratios (current ratio, quick ratio, gross margin, net margin, DSO, etc.).
**Response:** `{ "data": { "current_ratio": "2.00", "quick_ratio": "1.50", ... } }` (all values bcmath-compatible strings).

### `GET /statement-templates/vertical-analysis?from=...&to=...`
Every revenue/expense line as a percentage of total revenue.
**Response:** `{ "data": { "lines": [ { account, amount, percent }, ... ] } }`

### `GET /statement-templates/horizontal-analysis?from1=...&to1=...&from2=...&to2=...`
Period-over-period variance.
**Response:** `{ "data": { "lines": [ { account, period1, period2, variance, variance_percent }, ... ] } }`

---

## Cross-cutting notes

**Error envelopes:** every error follows `{ error: "<slug>", message, errors? }`. Slugs you'll see from these routes:
- `validation_error` (422)
- `subscription_inactive` (403)
- `feature_not_available` (403)
- `2fa_required` (403)
- `invalid_channel` / `invalid_signature` / `webhook_secret_not_configured` (401, webhooks only)

**Idempotency:** of the routes above, only destructive / money-moving ones accept `Idempotency-Key`. The portal accept-invite / disputes / payment-plan endpoints currently do NOT. If you add a UUID header there, the middleware ignores it harmlessly — safe to always send.

**Pagination:** `{ data, links, meta }` Laravel standard. Query params: `per_page` (default 15, max 100).

**Dates:** all date-only fields are `YYYY-MM-DD`; datetimes are ISO 8601 with microseconds.

---

## Appendix · Behavior changes on existing routes

These aren't new endpoints — they're middleware changes to routes you're already calling. Worth listing in one place so the frontend can adjust its client layer.

### A. Idempotency-Key now enforced on financial writes

`Idempotency-Key: <UUID v4>` on any of these routes is recognised, cached for 24h, and replays the original response (with `X-Idempotency-Replay: true`) on retry. Before this batch, only `/subscription/subscribe` and `/subscription/renew` honored it — everything else silently ignored the header. List of routes now wired:

- `POST /api/v1/invoices` (+ PUT/DELETE `{invoice}`)
- `POST /api/v1/invoices/{invoice}/cancel`
- `POST /api/v1/invoices/{invoice}/post-to-gl`
- `POST /api/v1/invoices/{invoice}/credit-note`
- `POST /api/v1/invoices/{invoice}/send`
- `POST /api/v1/payments`
- `POST /api/v1/bills` (+ PUT/DELETE `{bill}`)
- `POST /api/v1/bills/{bill}/approve`
- `POST /api/v1/bills/{bill}/cancel`
- `POST /api/v1/bills/{bill}/payments`
- `POST /api/v1/journal-entries` (+ PUT/DELETE `{journalEntry}`)
- `POST /api/v1/journal-entries/{journalEntry}/reverse`
- `POST /api/v1/journal-entries/{journalEntry}/post`
- `POST /api/v1/payroll/{payrollRun}/calculate`
- `POST /api/v1/payroll/{payrollRun}/approve`
- `POST /api/v1/payroll/{payrollRun}/mark-paid`
- `POST /api/v1/eta/documents/{invoice}/submit`
- `POST /api/v1/eta/documents/{invoice}/cancel`
- `POST /api/v1/subscription/change-plan`
- `POST /api/v1/import`, `/import/clients`, `/import/accounts`, `/import/opening-balances`

Rules:
- Sending the header is **optional** — routes accept the request with or without it. No key = no replay protection.
- Bad format → `422 { error: "Idempotency-Key must be a valid UUID v4" }`.
- Concurrent retry during the 30s lock → `409 { error: "request_in_progress" }`.
- Only `2xx` responses are cached — a 500 or 422 won't poison future retries.

**Frontend action:** keep generating a UUID v4 per mutation on the routes above. The middleware replays the original status code (201 for create, 200 for action endpoints), so `response.status === 201` still reliably means "created".

### B. Messaging throttle renamed + raised

Was: inline `throttle:10,1` on WhatsApp / SMS routes.
Now: named limiter `throttle:messaging` — 30 requests/min per authenticated user (falls back to IP).

Affected routes:
- `POST /api/v1/messaging/whatsapp`
- `POST /api/v1/messaging/sms`

**Frontend action:** relax any client-side cooldown UX from "1 every 6 seconds" to "1 every 2 seconds" if applicable. 429 still returns the standard `Retry-After` header.

---

## Impersonation landing route (NEW — frontend-only)

When a SuperAdmin clicks **Impersonate** in the Filament admin panel, the backend mints a 1-hour Sanctum token and opens a new tab at:

```
{frontend_url}/auth/impersonate?token=<plaintext-sanctum-token>&redirect=<path>
```

**Frontend action — build this page:**

1. Read `token` and `redirect` from the query string.
2. Persist `token` the same way the normal login flow does (localStorage / auth store / cookie — whatever the app already uses for `Bearer` tokens).
3. Clear any previously cached user/tenant state so the SPA re-fetches `/api/v1/me` as the impersonated user.
4. Navigate (client-side) to the `redirect` path — default `/dashboard` if missing or not an internal path.
5. Show a persistent banner while the token is active: **"Impersonating {user.email} — end session"**. Clicking "end session" should drop the token and return to login.

**Security notes:**
- Reject any `redirect` value that isn't a relative path starting with `/` (defeats open-redirect).
- Token is single-use in spirit only — it's a normal Sanctum token that expires after 1 hour. No special revocation; just delete it from storage when the admin ends the impersonation.
- Do not log the token to analytics or error trackers.
