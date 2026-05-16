# muhasebi — API Feature Reference

Per-feature API documentation for the v1 tenant API. Super-admin (`/admin/*`) endpoints are deprecated and covered by the Filament panel — they are intentionally not documented here.

> Base URL: `/v1` · Auth: `Authorization: Bearer <sanctum-token>` · OpenAPI: `GET /v1/docs/spec`

## Overview

- **[FEATURES.md](FEATURES.md)** — one-line description of every module (read this first).

## Feature modules

| # | Module | Doc |
|---|--------|-----|
| 01 | Authentication & Account Management | [01-authentication.md](01-authentication.md) |
| 02 | Public Routes (landing, blog, plans, webhooks) | [02-public-routes.md](02-public-routes.md) |
| 03 | Dashboard, Activity Log, Audit Compliance, Notifications | [03-dashboard-activity.md](03-dashboard-activity.md) |
| 04 | Subscription & Plans | [04-subscription-plans.md](04-subscription-plans.md) |
| 05 | Clients & CRM | [05-clients.md](05-clients.md) |
| 06 | Accounting Core — Chart of Accounts, Journal Entries, Fiscal Calendar | [06-accounting-core.md](06-accounting-core.md) |
| 07 | Bank Reconciliation, Bank Connections, FX Revaluation | [07-bank-reconciliation.md](07-bank-reconciliation.md) |
| 08 | Invoicing & Receivables | [08-invoicing.md](08-invoicing.md) |
| 09 | Accounts Payable — Vendors, Bills, Bill Payments | [09-accounts-payable.md](09-accounts-payable.md) |
| 10 | Expenses & Expense Reports | [10-expenses.md](10-expenses.md) |
| 11 | Fixed Assets & Depreciation | [11-fixed-assets.md](11-fixed-assets.md) |
| 12 | Inventory Management | [12-inventory.md](12-inventory.md) |
| 13 | Cost Centers & Budgeting | [13-cost-centers-budgets.md](13-cost-centers-budgets.md) |
| 14 | Tax Management — VAT, WHT, Corporate Tax | [14-tax-management.md](14-tax-management.md) |
| 15 | ETA E-Invoicing (Egyptian Tax Authority) | [15-eta-einvoicing.md](15-eta-einvoicing.md) |
| 16 | Payroll, Attendance, Leave — Egyptian Labor Law | [16-payroll-hr.md](16-payroll-hr.md) |
| 17 | Timesheets & Time Tracking | [17-timesheets.md](17-timesheets.md) |
| 18 | Engagements & Working Papers (Professional Services) | [18-engagements.md](18-engagements.md) |
| 19 | Reporting & Analytics | [19-reporting.md](19-reporting.md) |
| 20 | Approval Workflows & Alert Rules | [20-approvals-alerts.md](20-approvals-alerts.md) |
| 21 | Documents & Files | [21-documents.md](21-documents.md) |
| 22 | E-Commerce Integration | [22-ecommerce.md](22-ecommerce.md) |
| 23 | Messaging — WhatsApp & SMS via Beon.chat | [23-messaging.md](23-messaging.md) |
| 24 | Currency Management | [24-currency.md](24-currency.md) |
| 25 | Team & Onboarding | [25-team-onboarding.md](25-team-onboarding.md) |
| 26 | Webhooks & Settings (outbound) | [26-webhooks-settings.md](26-webhooks-settings.md) |
| 27 | Data Import (CSV/XLSX) | [27-data-import.md](27-data-import.md) |
| 28 | Client Portal (portal users, `/v1/portal/*`) | [28-client-portal.md](28-client-portal.md) |

## Conventions used in every doc

- **Access & Auth** — authentication requirement, tenant scope, permissions (`permission:*`), feature flags (`feature:*`), rate limits.
- **Endpoints** — one block per route with path params, query params, request body, response shape, and notes.
- Response envelope across the API is `{ "data": ..., "meta": ... }`; validation errors are `{ "message": "...", "errors": { "field": ["..."] } }` (HTTP 422).
- Tenant-scoped routes run through `tenant, active, enforce.2fa, set_timezone, set_locale, meter.usage`.

## Companion docs

- **Frontend brief:** [`../frontend/PROMPT.md`](../frontend/PROMPT.md), [`../frontend/IA.md`](../frontend/IA.md)
- **Customer brochure:** [`../marketing/features-brochure.pdf`](../marketing/features-brochure.pdf) (source: `.html` + `.md`)
