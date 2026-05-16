# Subscription & Plans

> Tenant self-service subscription management: subscribe, change plan, cancel, renew, track usage and billing history. Gates the feature flags that the rest of the app reads.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes (tenant admin UI).
- **Permissions:** `manage_subscription` on all endpoints.
- **Feature flags:** none (subscription management is always available).
- **Rate limits:** `throttle:5,1` on subscribe / cancel / renew / change-plan.
- **Idempotency:** `idempotent` middleware on subscribe + renew. `no-duplicate` middleware on subscribe / cancel / renew. Pass `Idempotency-Key: <uuid>` header on write calls.

## Endpoints

### `GET /v1/subscription`
Current tenant subscription.
**Response 200:**
```json
{
  "data": {
    "plan": { "id": 3, "slug": "growth", "name": "Growth", "price_egp_monthly": 999 },
    "status": "active | trialing | past_due | canceled",
    "billing_cycle": "monthly | quarterly | annual",
    "current_period_start": "2026-04-01",
    "current_period_end": "2026-05-01",
    "trial_ends_at": null,
    "auto_renew": true,
    "enabled_features": ["invoicing","payroll","eta_einvoice"],
    "limits": { "users": 10, "invoices_per_month": 500 }
  }
}
```

### `POST /v1/subscription/subscribe`
Subscribe or resubscribe a tenant.
**Middleware:** `throttle:5,1`, `idempotent`, `no-duplicate`
```json
{
  "plan_id": "int (required)",
  "billing_cycle": "monthly | quarterly | annual (required)",
  "payment_method": "paymob | fawry | bank_transfer (required)",
  "coupon_code": "string (optional)"
}
```
**Response 200:** subscription payload + payment redirect URL when applicable.

### `POST /v1/subscription/change-plan`
Upgrade or downgrade.
**Middleware:** `throttle:5,1`
```json
{
  "new_plan_id": "int (required)",
  "effective_date": "immediate | end_of_period (default: immediate)"
}
```
Proration is computed server-side. Immediate upgrades charge the difference now; downgrades take effect at period end by default.

### `POST /v1/subscription/renew`
Manual renewal.
**Middleware:** `throttle:5,1`, `idempotent`, `no-duplicate`
```json
{ "billing_cycle": "monthly | annual (optional)" }
```

### `POST /v1/subscription/cancel`
Cancel at end of period.
**Middleware:** `throttle:5,1`, `no-duplicate`
```json
{ "reason": "string (optional) — internal feedback", "immediate": false }
```

### `GET /v1/subscription/usage`
Current period consumption vs limits.
```json
{
  "data": {
    "period": { "start": "...", "end": "..." },
    "users": { "used": 4, "limit": 10 },
    "invoices_created": { "used": 312, "limit": 500 },
    "storage_gb": { "used": 1.7, "limit": 10 },
    "api_calls": { "used": 14200, "limit": 100000 }
  }
}
```

### `GET /v1/subscription/usage-history`
Historical usage by month.
**Query:** `from, to, metric`.

### `GET /v1/subscription/payments`
Invoice / receipt history for the subscription itself (platform charges).
**Query:** `per_page, status`.

---

## Notes
- The `features` array on the subscription drives the backend `feature:*` middleware that gates every other module. The frontend MUST read this list from `/v1/me` or `/v1/subscription` and hide nav items the tenant's plan doesn't include.
- Usage is enforced by the `meter.usage` middleware (tenant-scoped routes) and feature gates.
- Webhook callbacks from Paymob / Fawry at `/v1/webhooks/paymob` and `/v1/webhooks/fawry` finalize subscription payments — do not rely on subscribe response alone.
- `limits` of `-1` means unlimited.
