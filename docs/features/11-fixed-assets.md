# Fixed Assets & Depreciation

> Asset register with categories, depreciation schedules (straight-line, declining balance), disposal workflows, roll-forward reports.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_fixed_assets`.
- **Feature flag:** `fixed_assets`.
- **Rate limits:** none.

## Asset Categories

### `GET /v1/asset-categories`
List.

### `POST /v1/asset-categories`
```json
{
  "name": "string (required)",
  "name_ar": "string (required)",
  "useful_life_years": "number (required)",
  "salvage_rate": "number (optional, 0-1, default 0)",
  "depreciation_method": "straight_line | declining_balance | units_of_production (default straight_line)",
  "asset_account_id": "int (required)",
  "accumulated_depreciation_account_id": "int (required)",
  "depreciation_expense_account_id": "int (required)"
}
```

### `GET|PUT|DELETE /v1/asset-categories/{assetCategory}`

---

## Fixed Assets

### `GET /v1/fixed-assets`
**Query:** `category_id, status (active|disposed|fully_depreciated), search, per_page`.

### `POST /v1/fixed-assets`
```json
{
  "category_id": "int (required)",
  "name": "string (required)",
  "asset_number": "string (optional, auto-generated if omitted)",
  "description": "string (optional)",
  "acquisition_date": "YYYY-MM-DD (required)",
  "cost": "number (required) — purchase cost",
  "currency": "string (optional, default EGP)",
  "useful_life_years": "number (optional, inherits from category)",
  "salvage_value": "number (optional)",
  "depreciation_method": "straight_line | declining_balance (optional, inherits)",
  "in_service_date": "YYYY-MM-DD (optional, defaults to acquisition)",
  "location": "string (optional)",
  "custodian_id": "int (optional) — employee",
  "vendor_id": "int (optional)",
  "invoice_reference": "string (optional)",
  "cost_center_id": "int (optional)"
}
```

### `GET /v1/fixed-assets/{fixedAsset}`
```json
{
  "data": {
    "asset_number": "FA-2026-0042",
    "name": "...",
    "cost": 500000,
    "accumulated_depreciation": 125000,
    "book_value": 375000,
    "status": "active",
    "depreciation_schedule": [
      { "period": "2026-04", "amount": 10416.67, "posted": true }
    ]
  }
}
```

### `PUT /v1/fixed-assets/{fixedAsset}` / `DELETE /v1/fixed-assets/{fixedAsset}`
Update / soft-delete (only before first depreciation post).

### `GET /v1/fixed-assets/{fixedAsset}/depreciation-schedule`
Full monthly / periodic depreciation plan.

### `POST /v1/fixed-assets/depreciate`
Run depreciation for a period across selected (or all) assets.
```json
{
  "period_end": "YYYY-MM-DD (required)",
  "asset_ids": "int[] (optional, omit = all)"
}
```
Creates a bulk JE (DR Depreciation Expense, CR Accumulated Depreciation) per asset.

### `GET /v1/fixed-assets/reports/register`
Full asset register (tabular).
**Query:** `as_of_date, format (json|csv|pdf)`.

### `GET /v1/fixed-assets/reports/roll-forward`
Movement report for a period (beginning balance → additions → disposals → depreciation → ending balance).
**Query:** `from_date, to_date`.

---

## Disposals

### `GET /v1/fixed-assets/{fixedAsset}/disposals`
Disposal history for one asset.

### `POST /v1/fixed-assets/{fixedAsset}/dispose`
```json
{
  "disposal_date": "YYYY-MM-DD (required)",
  "method": "sale | scrap | donation | trade_in (required)",
  "proceeds": "number (optional, default 0)",
  "buyer_id": "int (optional) — client",
  "account_id": "int (required) — gain/loss account",
  "notes": "string (optional)"
}
```
Generates the disposal JE: derecognize cost + accumulated depreciation, recognize gain/loss, record proceeds.

## Notes
- Depreciation posts are auto-reversible in the current period; once the period closes, reversals require a correction JE.
- Asset numbering follows the pattern `FA-{FY}-{SEQ}` by default, configurable via Invoice Settings.
- Component accounting (separately depreciating parts) is supported by linking parent/child assets.
