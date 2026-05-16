# muhasebi — Feature List

> Multi-tenant accounting SaaS for the Egyptian market. Arabic/English bilingual, RTL-first, ETA-compliant. Designed for Egyptian SMEs, accountants, bookkeepers, and their clients.

## Core Accounting
- **Chart of Accounts** — tree-structured general-ledger accounts (Asset/Liability/Equity/Revenue/Expense) with bilingual names (`name_ar`/`name_en`), hierarchical view, and AI-suggested categorization via `AccountSuggestionController`.
- **Journal Entries** — double-en12try bookkeeping with draft, post, reverse, and delete actions. Enforces balanced debit/credit per entry and per-line account posting.
- **Recurring Journal Entries** — schedule-driven templates (monthly/quarterly/yearly) that auto-generate postings on due dates with toggle/pause support.
- **Fiscal Years & Periods** — fiscal-year setup, period close/reopen, and period-locking guard against backdated entries.
- **Opening Balances Import** — CSV-driven opening-balance import tied to a fiscal year for first-time tenant onboarding.

## Financial Operations
- **Invoicing & Receivables** — customer invoices with line items, VAT, discounts, GL posting, cancellation, credit notes, PDF rendering, and send-by-email. Includes pre-check validation and write-off/escalate actions for collections.
- **Recurring Invoices** — scheduled invoice generation with template line items and client binding.
- **Payments (AR)** — invoice payments across multiple gateways and methods, with idempotent posting and reversal/void.
- **Collections Management** — overview dashboard, action logging (email/SMS/call), per-client summary, and effectiveness reporting.
- **Aging Reminders** — configurable dunning sequences (days past due, frequency, recipients), history, and manual trigger.
- **Invoice Settings** — tenant-level defaults (numbering, terms, currency, footer) that flow into every invoice.
- **Accounts Payable (Bills & Vendors)** — vendor master data, statements, aging; bills with approve/cancel; bill payments with void.
- **Bank Reconciliation** — statement import, auto-match, manual match/unmatch/exclude, smart-match, auto-post, categorization rules, learn-from-user, and completion workflow.
- **Bank Connections** — aggregator-style bank connections with balance sync, statement import, supported-format lookup, and connection instructions (for banks that don't yet offer APIs in Egypt).
- **FX Revaluation** — period-end foreign-currency revaluation per account with post/reverse controls.
- **Currency Management** — multi-currency support with conversion endpoint and rate history (USD, EUR, SAR, AED, etc. against EGP).
- **Expenses** — expense categories, expense entries with receipts, submit/approve/reject/reimburse workflow, bulk submission, and summary reports.
- **Expense Reports** — grouped expense bundles with approval routing and per-report totals.
- **Fixed Assets & Depreciation** — asset categories (useful life, salvage, method), asset register, depreciation schedule, monthly depreciation run, roll-forward, and disposals (proceeds, gain/loss posting).
- **Inventory Management** — product categories, SKUs, stock movements (in/out/adjustment), stock report, low-stock alerts, valuation (FIFO/LIFO/weighted avg), turnover analysis, and per-product movement history.
- **Cost Centers** — code-named segments with parent hierarchy, manager assignment, P&L per cost center, cost-analysis and allocation reporting.
- **Budgeting** — fiscal-year budgets with per-account lines, approval, and variance reports (budget vs actual).

## Payroll & HR (Egyptian Labor Law)
- **Employees** — employee master data (hire date, salary, bank account, job title, department).
- **Payroll Runs** — period-based runs with calculate/approve/mark-paid lifecycle and itemized payslips.
- **Salary Components** — fixed/variable earnings and deductions, taxability flags, frequency, employee-level assignment.
- **Loans & Advances** — employee loans with installment schedule, recording, and cancellation.
- **Leave Management** — leave types, leave requests with approve/reject/cancel, and per-employee leave balance.
- **Attendance** — check-in/check-out capture, bulk-import day records, and per-employee attendance summaries.
- **Egyptian Labor Law Calculators** — overtime per Law 12/2003, end-of-service gratuity, statutory leave entitlement, and minimum-wage validation.
- **Social Insurance** — calculator against current Egyptian rates, monthly report for Form 1/Form 2, and employee registration tracking.
- **Payslips** — downloadable PDF payslips per employee per run.

## Time & Engagements (Professional Services)
- **Timesheets** — weekly timesheets, submit/reject/approve (including bulk-approve), summary by employee and period.
- **Timers** — live start/stop timers with description, project/task linking, and discard support.
- **Time Billing** — preview and generate client invoices from approved billable timesheets.
- **Engagements** — client engagements (audit, tax, advisory, bookkeeping) with budget, time allocation, deliverables, and status tracking.
- **Working Papers** — engagement-scoped working papers with preparer/reviewer sign-off workflow.

## Compliance & Tax (Egypt-specific)
- **ETA E-Invoicing** — full Egyptian Tax Authority integration: settings (tax number, legal name, activity, branch), document prepare/submit/cancel, status checks, reconciliation, compliance dashboard, and bulk retry/status-check.
- **ETA Item Codes (GPC/EGS)** — item-code catalog with bulk import, bulk assign, auto-assign, usage report, unmapped-lines view, suggestions, and product/account mappings.
- **VAT Returns** — periodic VAT return calculation, filing, and payment recording with PDF output.
- **Withholding Tax (WHT)** — WHT certificates: generate, issue, submit; WHT report with PDF.
- **Corporate Tax** — annual corporate-tax return calculation with adjustments ledger (add/remove).
- **Tax Adjustments** — per fiscal-year manual tax adjustments (permanent/temporary differences) feeding the corporate-tax return.
- **Audit & Compliance** — user-access reports, data-change log, high-risk events, segregation-of-duties analysis, export, and compliance summary.
- **Activity Log** — tenant-wide audit trail with stats, filtering, and per-activity detail view.

## Reporting & Analytics
- **Core Financial Reports** — Trial Balance, Account Ledger, Income Statement (P&L), Balance Sheet, Cash Flow — each available as JSON and PDF, with comparative (period-over-period) variants for P&L and Balance Sheet.
- **Receivables Reports** — AR aging and per-client statement.
- **Tax Reports** — VAT return and WHT report (PDF-ready).
- **Executive Dashboard** — overview, revenue analysis, cash-flow view, profitability, KPIs, and period comparison.
- **Custom Reports** — drag-and-drop query definitions (columns, filters, grouping, sorting) saved per tenant, with execute/run.
- **Scheduled Reports** — cron-scheduled report emails with recipients, toggle, and send-now.
- **Anomaly Detection** — duplicates, unusual amounts, missing-sequence detection, weekend entries — catches irregularities for accountants.
- **Async PDF Generation** — background PDF jobs for heavy reports.
- **Data Export** — CSV export of clients, invoices, and journal entries (throttled).

## Client Engagement
- **Clients / CRM Lite** — client master data (name, tax ID, credit limit, balance), soft-delete/restore, toggle-active, per-client messaging, and CSV import.
- **Client Portal** — tenant-branded portal login for client users: dashboard (total due, paid, overdue), profile, invoice list/detail/PDF, online payment (Paymob/Fawry), document upload/download, two-way messaging, and notifications.
- **Portal Invitations** — invite clients to the portal via email with role-based access to their own data only.
- **Messaging (WhatsApp / SMS via Beon.chat)** — send WhatsApp and SMS to clients, template library, conversation threads, inbound-message replies, and signed webhook ingestion.
- **Landing & Blog** — public marketing site: landing page, arbitrary content pages, contact form, blog with categories/tags/search, featured posts, and RSS feed.

## Approvals, Alerts & Workflow
- **Approval Workflows** — configurable multi-step approval rules scoped to entity types (invoices, bills, expenses, journal entries) with threshold conditions and approver rosters.
- **Approval Queue** — submit for approval, approve/reject, pending queue, and full history.
- **Alert Rules** — threshold-based alert rules (balance, aging, variance) with email recipients, enable/disable, and firing history.
- **Notifications** — in-app notification center (list, unread count, mark-read, mark-all, delete) with push via device tokens.

## Integrations
- **E-Commerce Integration** — Shopify / WooCommerce channels with sync, signed webhooks, dashboard, order-to-invoice conversion, and bulk conversion.
- **Payment Gateways** — Paymob and Fawry integration (signed webhooks) for portal invoice payments.
- **Webhooks (Outbound)** — tenant-defined webhook endpoints with event subscriptions, delivery history, and failure tracking.
- **Bank Data Integrations** — pluggable bank connections for statement import and balance sync (see Financial Operations).

## Data & Imports
- **CSV / XLSX Import** — unified importer with templates for clients, accounts, invoices, bills, and opening balances; match-strategy selection and per-job error reporting.
- **Documents & Files** — document library with per-tenant storage quota, bulk upload, archive/unarchive, tags, and download.

## Platform
- **Multi-Tenancy** — tenant-scoped data isolation; every request carries tenant context via middleware (`tenant`, `active`) and all business endpoints are tenant-bound.
- **Authentication (Sanctum)** — email/password login, registration with company, logout, `/me`, profile update, change password — all rate-limited.
- **Two-Factor Authentication (TOTP)** — enable/disable/verify 2FA; `enforce.2fa` middleware gates sensitive tenant routes. Super-admin 2FA is handled in the Filament panel.
- **RBAC (Roles & Permissions)** — granular permissions (`manage_invoices`, `post_journal_entries`, `manage_payroll`, `view_reports`, `manage_tax`, etc.) gated at the route layer via `permission:*`.
- **Subscription & Plans** — public plan catalog; tenant subscribe/cancel/renew/change-plan with billing cycles (monthly/quarterly/annual), usage metering, usage history, and payment history. Idempotent and throttled.
- **Plan Gating (feature flags)** — every premium module is gated by `feature:*` middleware (e.g. `feature:e_invoice`, `feature:payroll`, `feature:custom_reports`). Frontend hides nav items the tenant's plan doesn't include.
- **Team Management** — invite team members, assign/change roles, toggle active, remove users.
- **Onboarding (classic + wizard)** — step-wise onboarding: progress tracking, complete/skip, set up Chart of Accounts (from template), set up fiscal year, load sample data, invite first team member. The newer wizard adds template selection and opening-balance import.
- **User Preferences** — theme (light/dark), language (ar/en), sidebar state, keyboard-shortcut toggle, reset-to-defaults, and shortcut reference.
- **Notification Preferences** — channel-level opt-in (email/SMS/push) per event type.
- **Device Tokens** — register/unregister mobile push tokens for native apps.
- **Dashboard** — tenant home dashboard (revenue, invoices due, outstanding AR, key metrics).
- **Landing Page Settings** — tenant-editable marketing landing page content.
- **Health & API Docs** — public `/health` endpoint, `/v1/docs` Swagger UI and OpenAPI spec at `/v1/docs/spec`.

---

## Feature Count Summary

| Domain | Modules |
|---|---|
| Core Accounting | 5 |
| Financial Operations | 15 |
| Payroll & HR | 9 |
| Time & Engagements | 5 |
| Compliance & Tax (Egypt) | 8 |
| Reporting & Analytics | 9 |
| Client Engagement | 5 |
| Approvals, Alerts & Workflow | 4 |
| Integrations | 4 |
| Data & Imports | 2 |
| Platform | 14 |
| **Total feature modules** | **~80** |
| **Total non-admin API routes** | **~380+** |

All monetary values default to **EGP**. All resources are tenant-scoped. All writes pass through permission + feature-flag middleware. All bilingual fields use the `name_ar` / `name_en` convention.
