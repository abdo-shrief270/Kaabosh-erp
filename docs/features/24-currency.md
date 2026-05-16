# Currency Management

> Read the tenant's active currency catalogue, convert amounts between currencies on a specified date, and pull exchange-rate history for any pair.

## Access & Auth
- **Authentication:** Bearer token via Sanctum (`auth:sanctum`)
- **Tenant scope:** yes — routes live under the `tenant, active, enforce.2fa, set_timezone, set_locale, meter.usage` group
- **Permissions required:** none beyond being authenticated inside an active tenant (any tenant role can read / convert)
- **Feature flags:** none
- **Rate limits:** standard authenticated limits

Rates are stored per currency pair per day. The tenant base currency is typically `EGP`. Currency codes are ISO 4217 three-letter codes (uppercased by the controller).

**Note:** Creating currencies (`POST /v1/currencies`) and setting rates (`POST /v1/currencies/set-rate`) are SuperAdmin-only and documented with the platform admin endpoints, not here.

---

## Endpoints

### `GET /v1/currencies` — List active currencies
**Purpose:** Returns the catalogue of enabled currencies available to the tenant for invoicing, reporting, and reconciliation.
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa`
**Response 200:**
```json
{
  "data": [
    { "code": "EGP", "name_ar": "جنيه مصري", "name_en": "Egyptian Pound", "symbol": "ج.م", "decimal_places": 2, "is_active": true },
    { "code": "USD", "name_ar": "دولار أمريكي", "name_en": "US Dollar",     "symbol": "$",   "decimal_places": 2, "is_active": true },
    { "code": "EUR", "name_ar": "يورو",          "name_en": "Euro",          "symbol": "€",   "decimal_places": 2, "is_active": true },
    { "code": "SAR", "name_ar": "ريال سعودي",    "name_en": "Saudi Riyal",   "symbol": "ر.س", "decimal_places": 2, "is_active": true }
  ]
}
```
**Notes:** Read-only. Only currencies with `is_active=true` are returned.

---

### `POST /v1/currencies/convert` — Convert amount
**Purpose:** Converts a monetary amount between two currencies using the rate on the given date (defaults to today).
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa`
**Request body:**
```json
{
  "amount": 1000.00,
  "from": "USD (required, ISO 4217, exactly 3 chars)",
  "to":   "EGP (required)",
  "date": "2026-04-18 (optional, defaults to today)"
}
```
**Response 200:**
```json
{
  "data": {
    "amount": 1000.00,
    "from": "USD",
    "to": "EGP",
    "rate": 48.75,
    "converted": 48750.00
  }
}
```
**Response 404 (no rate available):**
```json
{ "error": "rate_not_found", "message": "Exchange rate not available for this currency pair." }
```
**Notes:** The `from` and `to` inputs are uppercased automatically. If you request a date earlier than the oldest stored rate, the service returns 404 rather than extrapolating.

---

### `GET /v1/currencies/rate-history` — Rate history for a pair
**Purpose:** Returns the daily exchange-rate series for the last N days (max 365).
**Middleware:** `auth:sanctum, tenant, active, enforce.2fa`
**Query params:**
- `from` (string, required, 3 chars)
- `to` (string, required, 3 chars)
- `days` (int, optional, 1..365, default 30)

**Response 200:**
```json
{
  "data": [
    { "date": "2026-03-20", "from": "USD", "to": "EGP", "rate": 48.10 },
    { "date": "2026-03-21", "from": "USD", "to": "EGP", "rate": 48.22 },
    { "date": "2026-04-18", "from": "USD", "to": "EGP", "rate": 48.75 }
  ]
}
```
**Notes:** Useful for charting rate trends; gaps on non-business days may be present depending on the data source (manual entry vs. scheduled feed).

---

## Related features
- **Reporting** — all financial report endpoints accept an optional `?currency=USD|...` query that re-expresses balances using `ReportCurrencyConverter` backed by this service.
- **FX Revaluation** (`/v1/fx-revaluations`) — month-end revaluation of foreign-currency balances uses these rates.
- **Invoicing** — multi-currency invoices store both the source currency and the base-currency equivalent computed via the rate on the invoice date.
- **Subscriptions** — plan prices are quoted in a single base currency; conversion displayed at checkout uses these rates.
