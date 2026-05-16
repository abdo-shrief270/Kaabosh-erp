# Webhooks & Settings (tenant-side)

> Configure outbound webhooks to receive domain events (invoice.created, payment.received, etc.), inspect delivery history, and manage tenant landing-page content.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_settings` — webhooks CRUD + events.
  - `manage_landing_page` — landing page settings.
- **Feature flag:** none.
- **Rate limits:** none.

## Webhook Endpoints

### `GET /v1/webhooks/events`
List the catalog of event types available for subscription.
**Response:**
```json
{
  "data": [
    { "event": "invoice.created", "description": "..." },
    { "event": "invoice.paid", "description": "..." },
    { "event": "payment.received", "description": "..." },
    { "event": "bill.approved", "description": "..." },
    { "event": "payroll.approved", "description": "..." },
    { "event": "approval.requested", "description": "..." }
  ]
}
```

### `GET /v1/webhooks`
List configured endpoints.

### `POST /v1/webhooks`
```json
{
  "url": "string (required, https)",
  "events": ["invoice.created","payment.received"],
  "active": true,
  "description": "string (optional)"
}
```
**Response:** includes a `secret` used by the receiver to verify `X-Muhasebi-Signature` (HMAC-SHA256). Shown ONCE on creation; rotate if lost.

### `PUT /v1/webhooks/{webhookEndpoint}`
Update url/events/active.

### `DELETE /v1/webhooks/{webhookEndpoint}`

### `GET /v1/webhooks/{webhookEndpoint}/deliveries`
Delivery log with attempts, response codes, retries.
**Query:** `status (success|failed|pending), from_date, to_date, per_page`.

---

## Landing Page Settings (`permission: manage_landing_page`)

For tenants whose plan includes a branded public landing page.

### `GET /v1/landing-page-settings`
```json
{
  "data": {
    "enabled": true,
    "hero_title": { "ar": "...", "en": "..." },
    "logo_url": "...",
    "primary_color": "#0d7b74",
    "pages": [ { "slug": "about", "title": "...", "body": "..." } ]
  }
}
```

### `PUT /v1/landing-page-settings`
Same shape. Updates tenant's public marketing site.

## Notes
- Webhook payloads are signed with HMAC-SHA256 using the endpoint secret. Timestamp tolerance is 5 minutes.
- Retries: exponential backoff up to 24h (delays ~1m, 5m, 30m, 2h, 12h, 24h).
- Outbound webhooks respect the tenant's feature plan and can be disabled globally for trialing accounts.
