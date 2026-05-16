# Messaging (Beon.chat WhatsApp/SMS)

> Send WhatsApp / SMS messages to clients, browse the shared Beon.chat conversation inbox, reply to customer threads, and receive inbound messages via a signature-verified webhook. Powered by Beon.chat's Egyptian messaging API.

## Access & Auth
- **Authentication:** Bearer token via Sanctum (`auth:sanctum`) — except the inbound webhook which is signature-verified and public.
- **Tenant scope:** yes (`tenant, active, enforce.2fa`)
- **Permissions required:** `permission:manage_clients`
- **Feature flags:** `feature:clients`
- **Rate limits:** `throttle:10,1` on outbound send endpoints (WhatsApp and SMS) — 10 sends per minute per user.

Messaging requires a configured Beon.chat tenant integration; calls return HTTP 422 with `error=not_configured` if the provider isn't set up. WhatsApp supports free-form messages up to 4,096 characters and template-based messages; SMS is limited to 160 characters per send.

---

## Outbound Sending

### `POST /v1/messaging/whatsapp` — Send WhatsApp message
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:clients, permission:manage_clients, throttle:10,1`
**Request body:**
```json
{
  "phone": "+201001234567 (required, max 20 chars, E.164 preferred)",
  "message": "مرحباً، فاتورتك INV-2026-0033 جاهزة (required, max 4096)",
  "template_name": "invoice_ready (optional)",
  "template_params": { "invoice_number": "INV-2026-0033", "amount": "5700.00 EGP" }
}
```
**Response 200:**
```json
{
  "message": "WhatsApp message sent.",
  "data": { "message_id": "bc_01HX9Y..." }
}
```
**Response 422 (not configured):**
```json
{ "error": "not_configured", "message": "WhatsApp messaging is not configured. Contact the administrator." }
```
**Response 422 (send failure):**
```json
{ "error": "send_failed", "message": "<provider error detail>" }
```
**Notes:** When `template_name` is present, the message body is rendered from the named Beon.chat template and `template_params` interpolate placeholders. Without a template, the raw `message` is sent (only supported inside the 24-hour customer service window on WhatsApp Business).

---

### `POST /v1/messaging/sms` — Send SMS
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:clients, permission:manage_clients, throttle:10,1`
**Request body:**
```json
{
  "phone": "+201001234567 (required, max 20)",
  "message": "required, max 160 chars"
}
```
**Response 200:** `{ "message": "SMS sent." }`
**Response 422:** Same shape as WhatsApp failures.

---

## Templates

### `GET /v1/messaging/templates` — List WhatsApp templates
**Purpose:** Returns the catalogue of approved Beon.chat WhatsApp templates available to the tenant.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa, feature:clients, permission:manage_clients`
**Response 200:**
```json
{
  "data": [
    {
      "name": "invoice_ready",
      "language": "ar",
      "status": "approved",
      "body": "فاتورتك رقم {{invoice_number}} بقيمة {{amount}} جاهزة.",
      "parameters": ["invoice_number", "amount"]
    },
    { "name": "payment_reminder", "language": "ar", "status": "approved", "body": "...", "parameters": ["invoice_number", "due_date"] }
  ]
}
```

---

## Conversation Inbox

### `GET /v1/messaging/conversations` — List conversations
**Purpose:** Paginated view of the shared Beon.chat inbox (all channels, all agents).
**Query params:**
- `status` — e.g. `open | pending | closed`
- `channel` — `whatsapp | sms`
- `page` — provider page cursor
- `per_page` (default 15, max 100)

**Response 200:**
```json
{
  "data": [
    {
      "id": "conv_01HX...",
      "client_phone": "+201001234567",
      "channel": "whatsapp",
      "status": "open",
      "last_message_at": "2026-04-18T09:45:00Z",
      "unread_count": 2,
      "last_message_preview": "شكراً، هستلم الفاتورة بكرة"
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 42 }
}
```

---

### `GET /v1/messaging/conversations/{conversationId}` — Conversation messages
**Path params:** `conversationId` — opaque Beon.chat conversation id (string)
**Response 200:**
```json
{
  "data": {
    "id": "conv_01HX...",
    "client_phone": "+201001234567",
    "messages": [
      { "id": "msg_01", "direction": "inbound", "body": "مرحباً", "sent_at": "2026-04-18T09:40:00Z" },
      { "id": "msg_02", "direction": "outbound", "body": "أهلاً بحضرتك، كيف أقدر أساعدك؟", "sent_at": "2026-04-18T09:41:00Z", "sent_by": "user:5" }
    ]
  }
}
```

---

### `POST /v1/messaging/conversations/{conversationId}/reply` — Reply in a thread
**Request body:**
```json
{ "message": "required, max 4096" }
```
**Response 200:** `{ "success": true, "message_id": "msg_03" }`
**Response 422:** `{ "success": false, "error": "<detail>" }` — e.g. window expired.
**Notes:** Stays inside the same conversation thread; Beon.chat picks the correct channel (WhatsApp vs SMS) based on the original thread.

---

## Inbound Webhook (public)

### `POST /v1/webhooks/beon-chat` — Inbound message receiver
**Purpose:** Beon.chat POSTs every inbound message (client reply, delivery receipt, status change) to this endpoint.
**Middleware:** public — signature-verified via `X-Beon-Signature` or `X-Webhook-Signature` header.
**Request headers:** `X-Beon-Signature: <hmac-sha256>` (required; missing header returns 401).
**Request body:** Beon.chat native event payload (inbound message, delivery receipt, read receipt, conversation status change).
**Response 200:** `{ "received": true, "action": "<persisted_action>" }`
**Response 401:** `{ "error": "Missing signature" }`
**Notes:** Inbound messages are persisted, associated with the matching client record (by phone), and raised as in-app notifications for the tenant's users.

---

## Related features
- **Clients** (`/v1/clients/{client}/messages`, `/v1/clients/{client}/sendMessage`) — per-client conversation thread at the muhasebi API layer, separate from the Beon.chat shared inbox.
- **Collections** (`/v1/collections/actions`) — can log a `sms` or `whatsapp` action type that references a message id from this API.
- **Aging Reminders** — scheduled dunning emails + optional WhatsApp/SMS nudges pull templates from `GET /v1/messaging/templates`.
- **Notifications** — inbound messages surface in the tenant notification feed (`/v1/notifications`).
