# Clients & Client Portal

> Tenant-side CRM for managing client records, in-app messaging, and inviting clients to a self-service portal. The portal-USER endpoints (`/v1/portal/*`) are authenticated with a portal-scoped token — see `28-client-portal.md`.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_clients` (CRUD + messaging), `invite_client_portal` (portal invite).
- **Feature flags:** `clients` (CRUD), `client_portal` (invite).
- **Rate limits:** none explicit; `meter.usage` applies.

## Endpoints

### `GET /v1/clients`
Paginated client list.
**Query:** `per_page, search, status (active|inactive|archived), city, industry, sort`.

### `POST /v1/clients`
Create client.
```json
{
  "name": "string (required)",
  "email": "string (optional, email)",
  "phone": "string (optional)",
  "tax_id": "string (optional) — ETA tax registration number",
  "commercial_registration": "string (optional)",
  "website": "string (optional)",
  "city": "string (optional)",
  "country": "string (optional, ISO-3166-1 alpha-2, default EG)",
  "address": "string (optional)",
  "contact_person": "string (optional)",
  "industry": "string (optional)",
  "credit_limit": "number (optional, EGP)",
  "payment_terms": "string (optional) — e.g. Net 30",
  "currency": "string (optional, ISO-4217, default EGP)",
  "notes": "string (optional)"
}
```

### `GET /v1/clients/{client}`
```json
{
  "data": {
    "id": 1, "name": "...", "email": "...", "tax_id": "...",
    "balance": 12500.00, "currency": "EGP",
    "credit_limit": 100000, "status": "active",
    "contacts": [ ... ], "addresses": [ ... ],
    "recent_invoices": [ ... ], "open_invoices_count": 3,
    "aging_buckets": { "0_30": 5000, "31_60": 3500, "61_90": 0, "90_plus": 0 }
  }
}
```

### `PUT /v1/clients/{client}`
Same fields as create.

### `DELETE /v1/clients/{client}`
Soft-delete.

### `POST /v1/clients/{client}/restore`
Restore a soft-deleted client.

### `PATCH /v1/clients/{client}/toggle-active`
Flip `active` state.

---

## Client Messaging

### `GET /v1/clients/{client}/messages`
Thread history between the tenant and the client. Paginated.

### `POST /v1/clients/{client}/messages`
Send a message to the client (email + optional WhatsApp/SMS via Beon.chat).
```json
{
  "channel": "email | whatsapp | sms (required)",
  "subject": "string (optional, email only)",
  "message": "string (required)",
  "attachments": ["file_id | url"]
}
```

---

## Portal Invitations (feature: `client_portal`)

### `POST /v1/clients/{client}/invite-portal`
Provisions a portal user for a client contact.
**Permission:** `invite_client_portal`
```json
{
  "email": "string (required)",
  "name": "string (required)",
  "send_email": true
}
```
**Effect:** creates portal user, emails a one-time magic link, records invitation. Portal user uses `/v1/portal/*` endpoints afterwards (see `28-client-portal.md`).

---

## CSV Import

### `POST /v1/import/clients`
Bulk import clients from CSV/XLSX.
**Request:** multipart with `file` field.
Returns an `ImportJobResource` — poll `/v1/import/{importJob}` for progress.

## Notes
- Egyptian tax: `tax_id` is required to issue ETA-compliant invoices to the client. Validated against ETA format when provided.
- Balance, aging, and open-invoice counts on the detail response are computed on read (cached for 60s).
- Messages via `whatsapp|sms` are routed through Beon.chat — see `23-messaging.md`.
