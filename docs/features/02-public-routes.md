# Public Routes

> Unauthenticated endpoints that power the marketing site (landing, blog, pricing), platform health, API discoverability, public contact form, and signed webhooks for payment gateways, e-commerce, and chat.

## Access & Auth
- **Authentication:** none (public). Webhooks are authenticated by platform signature.
- **Tenant scope:** no.
- **Permissions required:** none.
- **Feature flags:** none.
- **Rate limits:** `throttle:5,1` on contact submission.
- **Caching:** `cache.public:600` (10 min CDN/edge cache) on landing and page routes.

## Endpoints

### `GET /v1/plans`
**Purpose:** list all publicly visible subscription plans for the pricing page.
**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "slug": "growth",
      "name": "Growth",
      "price_egp_monthly": 999,
      "price_egp_yearly": 9990,
      "features": ["invoicing","payroll","eta_einvoice"],
      "limits": { "users": 10, "invoices_per_month": 500 }
    }
  ]
}
```

### `GET /v1/plans/{plan}`
Plan details by id or slug.

---

### `GET /v1/landing`
Homepage content (hero, modules, testimonials, blog highlights).
**Middleware:** `cache.public:600`

### `GET /v1/pages/{slug}`
Marketing CMS page (privacy, terms, about, etc.).
**Middleware:** `cache.public:600`

### `POST /v1/contact`
**Middleware:** `throttle:5,1`
```json
{
  "name": "string (required)",
  "email": "string (required, email)",
  "phone": "string (optional)",
  "subject": "string (required)",
  "message": "string (required)"
}
```
**Response 201:** `{ "message": "Received" }`

---

## Health & Docs

### `GET /v1/health`
Basic liveness probe. `{ "status": "ok", "time": "2026-04-18T12:34:56Z" }`.

### `GET /v1/docs`
Swagger / Redoc UI for the API spec.

### `GET /v1/docs/spec`
OpenAPI 3 JSON spec. Use this for client SDK generation.

---

## Blog (public content)

### `GET /v1/blog`
Paginated list. Query: `per_page`, `category`, `tag`.

### `GET /v1/blog/featured`
Editorially curated posts.

### `GET /v1/blog/categories`
List of categories with post counts.

### `GET /v1/blog/tags`
List of tags.

### `GET /v1/blog/search?q=...`
Full-text search across titles and bodies.

### `GET /v1/blog/{slug}`
Single post with markdown body, cover image, related posts.

### `GET /v1/blog/rss`
RSS 2.0 feed.

---

## Webhooks (signature-verified, no auth)

### `POST /v1/webhooks/paymob`
Paymob payment gateway callback. The controller verifies the HMAC signature (HMAC-SHA512 over the callback payload with the merchant HMAC key). On success, associated invoice is marked paid / payment recorded.

### `POST /v1/webhooks/fawry`
Fawry callback (Egyptian payment aggregator). Signature verification per Fawry's docs.

### `POST /v1/webhooks/beon-chat`
Incoming WhatsApp / SMS message from Beon.chat.
**Headers:** `X-Beon-Signature` or `X-Webhook-Signature` (required).
**Body:** platform-specific payload. Stored in messages table and linked to client by phone match.

### `POST /v1/webhooks/ecommerce/{platform}`
E-commerce platform order event (`platform` ∈ `shopify | woocommerce`). Signature verified per platform convention (e.g., `X-Shopify-Hmac-Sha256`).
**Effect:** creates an `EcommerceOrder` row; optionally auto-converts to invoice if channel is configured for it.

---

## Notes
- Public JSON responses include `Cache-Control` headers per the `cache.public` middleware.
- The OpenAPI spec at `/v1/docs/spec` is authoritative for schema shapes.
- Contact submissions create an internal lead record and trigger an email to the sales inbox.
