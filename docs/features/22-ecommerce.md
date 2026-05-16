# E-Commerce Integration

> Connect Shopify, WooCommerce, Salla, Zid, or a custom storefront; sync orders into muhasebi; convert them into accounting invoices one-by-one or in bulk; receive real-time updates via signed platform webhooks.

## Access & Auth
- **Authentication:** Bearer token via Sanctum (`auth:sanctum`) — except the inbound webhook which is signature-verified and public.
- **Tenant scope:** yes for authenticated routes (`tenant, active, enforce.2fa`)
- **Permissions required:** `permission:manage_integrations`
- **Feature flags:** none
- **Rate limits:** none beyond the standard authenticated limits; the webhook endpoint uses signature verification (per-platform HMAC) instead of rate limiting.

**Supported platforms:** `shopify, woocommerce, salla, zid, custom`.
Salla and Zid are the two dominant Arabic-region e-commerce platforms.

---

## Dashboard & Bulk Actions

### `GET /v1/ecommerce/dashboard` — Integration overview
**Purpose:** Aggregated counts and health metrics across all channels.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:manage_integrations`
**Response 200:**
```json
{
  "data": {
    "channels": { "total": 3, "active": 2, "by_platform": { "shopify": 1, "salla": 1, "woocommerce": 1 } },
    "orders": { "total": 842, "pending_conversion": 17, "converted": 815, "failed": 10 },
    "revenue": { "this_month": "125000.00", "last_month": "98000.00", "currency": "EGP" },
    "last_sync_at": "2026-04-18T08:30:00Z"
  }
}
```

### `POST /v1/ecommerce/bulk-convert` — Convert many orders to invoices
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:manage_integrations`
**Request body:**
```json
{ "order_ids": [101, 102, 103] }
```
Validation: `order_ids` is required, min 1, each must exist in `ecommerce_orders`.
**Response 200:**
```json
{
  "data": { "converted": 2, "skipped": 1, "failed": 0, "invoice_ids": [55, 56] },
  "message": "Converted 2 orders."
}
```
**Notes:** Already-converted orders are skipped (idempotent). Each order becomes one draft invoice assigned to the matched/created client.

---

## Channel CRUD

### `GET /v1/ecommerce/channels` — List connected channels
**Query params:** `platform`, `is_active`, `per_page (default 15, max 100)`
**Response 200:** Paginated `ECommerceChannel[]` with `orders_count`.

### `POST /v1/ecommerce/channels` — Connect a new channel
**Request body:**
```json
{
  "platform": "shopify | woocommerce | salla | zid | custom (required)",
  "name": "My Shopify Store (required, max 255)",
  "api_url": "https://mystore.myshopify.com (optional, URL)",
  "api_key": "shpat_xxxxxx (optional, encrypted at rest)",
  "api_secret": "optional, max 1000",
  "webhook_secret": "optional, shared HMAC secret for verifying inbound webhooks",
  "is_active": true,
  "settings": { "auto_convert": false, "default_tax_rate": 14.00 }
}
```
**Response 201:** `{ "data": {...}, "message": "E-commerce channel created." }`
**Notes:** `api_key`, `api_secret`, and `webhook_secret` are stored encrypted; the raw values are never returned in GET responses.

### `GET /v1/ecommerce/channels/{ecommerceChannel}` — Show
**Response 200:** `{ "data": { ..., "orders_count": 342 } }`

### `PUT /v1/ecommerce/channels/{ecommerceChannel}` — Update
**Request body:** Any subset of the store body (each field is `sometimes`).
**Response 200:** `{ "data": {...}, "message": "E-commerce channel updated." }`

### `DELETE /v1/ecommerce/channels/{ecommerceChannel}` — Delete
**Response 200:** `{ "message": "E-commerce channel deleted." }`
**Notes:** Does not cascade-delete synced orders; orders remain for accounting history.

### `POST /v1/ecommerce/channels/{ecommerceChannel}/sync` — Trigger order sync
**Purpose:** Pulls new/updated orders from the remote platform (since the last successful sync cursor) and stores them locally as `ECommerceOrder` records.
**Response 200:**
```json
{
  "data": { "synced": 12, "new": 8, "updated": 4, "last_order_at": "2026-04-18T07:55:00Z" },
  "message": "Synced 12 orders."
}
```
**Notes:** Safe to call repeatedly; the service maintains a per-channel cursor and dedupes by platform order id.

---

## Order Conversion

### `POST /v1/ecommerce/orders/{ecommerceOrder}/convert` — Convert a single order
**Purpose:** Creates a draft invoice from the e-commerce order (client matched/created by email, line items mapped to products, VAT recalculated).
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, permission:manage_integrations`
**Response 200:**
```json
{
  "data": {
    "order_id": 101,
    "invoice_id": 55,
    "invoice_number": "INV-2026-0055",
    "client_id": 12,
    "total": "1140.00",
    "status": "draft"
  },
  "message": "Order converted to invoice."
}
```
**Notes:** Idempotent — re-converting returns the existing invoice reference without duplicating.

---

## Inbound Webhook (public)

### `POST /v1/webhooks/ecommerce/{platform}` — Platform webhook receiver
**Purpose:** Handles order.created / order.updated / order.paid / order.refunded events from the connected store.
**Middleware:** signature-verified (per-platform HMAC using the channel's `webhook_secret`); no Sanctum auth.
**Path params:** `platform = shopify | woocommerce | salla | zid | custom`
**Request body:** Platform-native payload; muhasebi normalizes it before persisting.
**Response 200:**
```json
{ "received": true, "order_id": 101, "action": "upserted" }
```
**Notes:**
- Signature headers differ per platform: Shopify uses `X-Shopify-Hmac-Sha256`; WooCommerce uses `X-WC-Webhook-Signature`; Salla/Zid use their documented HMAC headers.
- A signature mismatch returns `401 Unauthorized` and the event is not persisted.
- If `settings.auto_convert=true` on the channel, matching orders are automatically converted to invoices on receipt.

---

## Related features
- **Invoicing** (`/v1/invoices/*`) — converted orders produce standard invoices that follow the normal invoice workflow (send, PDF, post-to-GL, credit notes).
- **Clients** (`/v1/clients`) — new buyers are auto-created; existing clients are matched by email/phone.
- **Webhooks & Settings** (`/v1/webhooks`) — outbound webhooks notify downstream systems when orders convert.
- **Payments** — converted invoices can receive payments via the regular `/v1/payments` flow or the client portal (Paymob / Fawry).
