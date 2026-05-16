# Inventory Management

> Product catalog (SKUs), stock movements, valuation (FIFO / LIFO / weighted average), low-stock alerts, turnover KPIs.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_inventory`.
- **Feature flag:** `inventory`.
- **Rate limits:** none.

## Product Categories

### `GET /v1/product-categories`
### `POST /v1/product-categories`
```json
{ "name": "string (required)", "description": "string (optional)" }
```
### `PUT /v1/product-categories/{productCategory}`
### `DELETE /v1/product-categories/{productCategory}`

---

## Products

### `GET /v1/products`
**Query:** `category_id, search, low_stock_only, per_page`.

### `POST /v1/products`
```json
{
  "category_id": "int (optional)",
  "sku": "string (required, unique per tenant)",
  "barcode": "string (optional)",
  "name_en": "string (optional)",
  "name_ar": "string (required)",
  "description": "string (optional)",
  "unit": "string (required) — e.g. 'pcs','kg','box'",
  "cost_price": "number (required)",
  "selling_price": "number (required)",
  "tax_rate": "number (optional, default 14)",
  "reorder_level": "number (optional)",
  "reorder_quantity": "number (optional)",
  "valuation_method": "fifo | lifo | weighted_average (default fifo)",
  "track_stock": true,
  "inventory_account_id": "int (optional)",
  "cogs_account_id": "int (optional)",
  "revenue_account_id": "int (optional)",
  "eta_item_code": "string (optional)",
  "eta_item_code_type": "string (optional) — GS1 | EGS | etc."
}
```

### `GET /v1/products/{product}`
```json
{
  "data": {
    "id": 1, "sku": "A-100", "name": "...",
    "current_stock": 42, "unit_cost": 150.00,
    "selling_price": 250.00, "reorder_level": 10,
    "low_stock_alert": false, "last_movement_at": "..."
  }
}
```

### `PUT|DELETE /v1/products/{product}`

### `GET /v1/products/{product}/movements`
Per-product movement history.

---

## Inventory Operations

### `POST /v1/inventory/movements`
Record a stock movement.
```json
{
  "product_id": "int (required)",
  "type": "in | out | adjustment | transfer",
  "quantity": "number (required, can be negative for adjustments)",
  "unit_cost": "number (optional, required for 'in')",
  "reference": "string (optional) — GRN, PO, invoice id",
  "reference_type": "string (optional)",
  "reference_id": "int (optional)",
  "date": "YYYY-MM-DD (required)",
  "location_from": "string (optional, for transfers)",
  "location_to": "string (optional, for transfers)",
  "notes": "string (optional)"
}
```

### `GET /v1/inventory/stock-report`
Current stock snapshot.
**Query:** `as_of_date, category_id, location, format (json|csv)`.

### `GET /v1/inventory/low-stock`
Products at or below reorder level.

### `GET /v1/inventory/valuation`
Inventory valuation per product and total.
**Query:** `method (fifo|lifo|weighted_average), as_of_date`.

### `GET /v1/inventory/turnover`
Turnover ratio + days-of-inventory per product.
**Query:** `from_date, to_date`.

## Notes
- Stock movements are immutable — corrections are new `adjustment` entries.
- Sales invoices with inventory items automatically create `out` movements when posted to GL.
- Bills with inventory items automatically create `in` movements on approval.
- COGS is calculated at the moment of sale according to the product's valuation method.
