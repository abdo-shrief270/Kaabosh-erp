# Client Portal (Portal Users — `/v1/portal/*`)

> Self-service endpoints used by the tenant's END CLIENTS, not tenant staff. Portal users authenticate with a portal-scoped Sanctum token issued via the invitation flow in `05-clients.md`. They see only their own invoices, documents, and messages within the tenant.

## Access & Auth
- **Authentication:** Bearer (Sanctum) — portal user token, not a tenant-staff token.
- **Tenant scope:** yes — portal users are scoped to the tenant that invited them.
- **Middleware:** `client_portal, tenant, active, set_timezone, set_locale`.
- **Permissions:** none (portal-scoped tokens authorize themselves).
- **Feature flag:** `client_portal` on the tenant's plan.
- **Rate limits:** none explicit.

## Dashboard & Profile

### `GET /v1/portal/dashboard`
Overview for the portal user.
```json
{
  "data": {
    "tenant": { "name": "...", "logo_url": "..." },
    "client": { "name": "...", "balance": 12500 },
    "summary": {
      "total_due": 12500,
      "overdue_count": 2,
      "paid_last_30d": 5000
    },
    "recent_invoices": [ ... ],
    "unread_messages": 1
  }
}
```

### `GET /v1/portal/profile`
The portal user's profile + linked client.

---

## Invoices

### `GET /v1/portal/invoices`
**Query:** `status (sent|partial|paid|overdue), from_date, to_date, per_page`.

### `GET /v1/portal/invoices/{invoice}`
Client-visible invoice (redacted internal fields).

### `POST /v1/portal/invoices/{invoice}/pay`
Initiate payment via a gateway.
```json
{
  "gateway": "paymob | fawry (required)",
  "return_url": "string (optional, for hosted checkout)"
}
```
**Response:** `{ data: { redirect_url, reference } }`. The gateway posts to the public webhook endpoint (see `02-public-routes.md`) to confirm settlement.

### `GET /v1/portal/invoices/{invoice}/pdf`
PDF download.

### `GET /v1/portal/payment-gateways`
List of gateways the tenant has enabled.
```json
{ "data": [ { "slug": "paymob", "name": "Paymob", "logo_url": "..." } ] }
```

---

## Documents

### `GET /v1/portal/documents`
Documents shared with the client.
**Query:** `category, search, per_page`.

### `POST /v1/portal/documents`
Upload (e.g., signed contract, receipt).
Multipart: `file`, `title`, `description`.

### `GET /v1/portal/documents/{document}/download`
Signed download URL.

---

## Messages

### `GET /v1/portal/messages`
Threads between the client and the tenant's team.

### `POST /v1/portal/messages`
```json
{ "content": "string (required)", "attachments": ["document_id (optional)"] }
```

### `GET /v1/portal/messages/{message}`
Single message.

### `POST /v1/portal/messages/{message}/read`
Mark read.

---

## Notifications

### `GET /v1/portal/notifications`
Portal user's notifications (invoice issued, payment confirmed, message received).

### `POST /v1/portal/notifications/{notification}/read`

### `POST /v1/portal/notifications/read-all`

## Notes
- Portal users have a distinct role (`client_portal_user`) and CANNOT access any tenant-staff endpoint, even with a valid token.
- The portal UI should use a visually distinct shell from the admin app (different layout, the tenant's branding via `/v1/landing-page-settings`).
- Payment redirects are tenant-specific: each tenant configures its own Paymob/Fawry merchant in platform settings.
- Portal users do not consume a `users` seat from the tenant plan; they count against `portal_users_limit` (separately metered).
