# Tax Management — VAT, WHT, Corporate Tax

> Egyptian tax compliance: Withholding Tax certificates, VAT returns, corporate tax computation, and tax adjustments at year-end.

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:** `manage_tax` on all endpoints.
- **Feature flag:** `tax`.
- **Rate limits:** none explicit.

## Withholding Tax Certificates

Under Egyptian Income Tax Law, WHT is deducted at source on certain payments to vendors (services, royalties, etc.) at rates typically between 1% and 5%.

### `GET /v1/wht-certificates`
**Query:** `vendor_id, status (draft|issued|submitted), from_date, to_date, per_page`.

### `POST /v1/wht-certificates/generate`
Generate a WHT certificate from a bill/payment.
```json
{
  "vendor_id": "int (required)",
  "bill_id": "int (optional)",
  "payment_id": "int (optional)",
  "amount": "number (required) — gross payment amount",
  "wht_rate": "number (required) — 0.01, 0.02, 0.05, etc.",
  "certificate_type": "service | royalty | professional_fee | other",
  "date": "YYYY-MM-DD (required)",
  "notes": "string (optional)"
}
```

### `GET /v1/wht-certificates/{whtCertificate}`
Full certificate payload including QR code for submission.

### `POST /v1/wht-certificates/{whtCertificate}/issue`
Issue (lock) the certificate; generates PDF and certificate number.

### `POST /v1/wht-certificates/{whtCertificate}/submit`
Mark as submitted to tax authority and record submission date/reference.
```json
{ "submission_reference": "string (optional)", "submission_date": "YYYY-MM-DD" }
```

---

## Tax Returns

### `GET /v1/tax-returns`
All filed and draft returns.
**Query:** `type (vat|corporate|wht), fiscal_year_id, status, per_page`.

### `GET /v1/tax-returns/{taxReturn}`
Full detail including calculation breakdown, GL support.

### `POST /v1/tax-returns/vat`
Calculate the VAT return for a period.
```json
{
  "period_start": "YYYY-MM-DD (required)",
  "period_end": "YYYY-MM-DD (required)",
  "include_draft_invoices": false
}
```
**Response:**
```json
{
  "data": {
    "period": { "start": "...", "end": "..." },
    "output_vat": 145000,
    "input_vat": 52000,
    "net_vat_payable": 93000,
    "rates_breakdown": { "14": { "sales": 1000000, "vat": 140000 } },
    "supporting_docs": [ /* invoice/bill ids */ ]
  }
}
```

### `POST /v1/tax-returns/corporate`
Calculate corporate income tax for a fiscal year.
```json
{
  "fiscal_year_id": "int (required)",
  "adjustments": [
    { "description": "Non-deductible entertainment", "amount": 25000, "type": "add_back" }
  ],
  "corporate_tax_rate": "number (optional, default 22.5)"
}
```

### `POST /v1/tax-returns/{taxReturn}/file`
Mark as officially filed with the Egyptian Tax Authority.
```json
{
  "filing_date": "YYYY-MM-DD (required)",
  "filing_reference": "string (required)",
  "filer_name": "string (optional)",
  "attachment": "document_id (optional)"
}
```

### `POST /v1/tax-returns/{taxReturn}/payment`
Record the tax payment.
```json
{
  "amount": "number (required)",
  "payment_date": "YYYY-MM-DD (required)",
  "reference": "string (optional)",
  "bank_account_id": "int (required)"
}
```

---

## Tax Adjustments (year-end)

### `GET /v1/tax-adjustments/{fiscalYear}`
List adjustments booked for a FY.

### `POST /v1/tax-adjustments`
```json
{
  "fiscal_year_id": "int (required)",
  "description": "string (required)",
  "amount": "number (required)",
  "account_id": "int (optional)",
  "adjustment_type": "add_back | deduction | deferred_tax | permanent_difference",
  "notes": "string (optional)"
}
```

### `DELETE /v1/tax-adjustments/{taxAdjustment}`
Remove. Not allowed once the related tax return is filed.

## Notes
- Corporate tax rate defaults to 22.5% (Egypt standard); financial services, oil/gas, and other sectors have different rates — override per return.
- VAT return periods are monthly for most registered companies.
- WHT certificates follow Egyptian Tax Authority templates; PDF output is formatted for official submission.
- See `15-eta-einvoicing.md` for real-time ETA e-invoice submission (separate workflow).
