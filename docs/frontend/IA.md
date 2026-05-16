# muhasebi — Information Architecture (Sidebar Navigation Tree)

> Full left-sidebar nav for the Tenant Admin Panel. Every leaf maps to a resource in the `/v1` API. Items gated by `feature:*` middleware on the backend must be hidden on the frontend when the tenant's plan does not include them (read from `GET /v1/me` + `GET /v1/subscription`).
>
> Legend:
> - `[feature:X]` — hide if tenant plan lacks feature `X`.
> - `[perm:Y]` — hide if user lacks permission `Y`.
> - `[role:portal]` — only visible in the Client Portal shell.
> - RTL: all `padding-inline-start`/`margin-inline-start` logical properties; icons mirror where directional.

---

## 1. Dashboard
- **Overview** — tenant KPIs, revenue, invoices due, outstanding AR, cash position.
- **Executive Dashboard** `[feature:reports]` `[perm:view_reports]`
  - Overview
  - Revenue Analysis
  - Cash Flow
  - Profitability
  - KPIs
  - Period Comparison
- **Anomalies** `[feature:reports]` `[perm:view_reports]`
  - All anomalies
  - Duplicates
  - Unusual amounts
  - Missing sequences
  - Weekend entries

---

## 2. Sales
- **Invoices** `[feature:invoicing]` `[perm:manage_invoices]`
  - All invoices
  - Draft
  - Sent
  - Paid
  - Overdue
  - New invoice
- **Recurring Invoices** `[feature:invoicing]`
- **Payments Received** `[feature:invoicing]` `[perm:manage_payments]`
- **Credit Notes** (under Invoices > actions)
- **Collections** `[feature:collections]` `[perm:manage_collections]`
  - Overview
  - Actions log
  - Client summary
  - Effectiveness report
- **Aging Reminders** `[perm:manage_settings]`
  - Settings
  - History
  - Trigger now
- **Invoice Settings** `[perm:manage_settings]`

---

## 3. Purchases
- **Vendors** `[feature:bills_vendors]` `[perm:manage_vendors]`
  - All vendors
  - Statements
  - Aging report
- **Bills** `[feature:bills_vendors]` `[perm:manage_bills]`
  - All bills
  - Pending approval
  - Paid
  - New bill
- **Bill Payments** `[feature:bills_vendors]`
- **Expenses** `[feature:expenses]` `[perm:manage_expenses]`
  - All expenses
  - My expenses
  - Pending approval
  - Reimbursements
  - Categories
  - Summary report
- **Expense Reports** `[feature:expenses]`

---

## 4. Banking
- **Bank Accounts** `[feature:accounting]`
- **Bank Connections** `[feature:accounting]`
  - Dashboard
  - All connections
  - Supported formats
  - Generate instruction
- **Reconciliations** `[feature:accounting]`
  - All reconciliations
  - In progress
  - Completed
  - Categorization rules
- **FX Revaluation** `[feature:accounting]`
- **Currencies**
  - Rate list
  - Converter
  - Rate history

---

## 5. Accounting
- **Chart of Accounts** `[feature:accounting]` `[perm:manage_accounts]`
  - Tree view
  - Flat view
  - Import CSV
- **Journal Entries** `[feature:accounting]` `[perm:manage_journal_entries]`
  - All entries
  - Draft
  - Posted
  - Reversed
  - New entry
  - Import opening balances
- **Recurring Journal Entries** `[feature:accounting]`
- **Fiscal Years** `[feature:accounting]` `[perm:post_journal_entries]`
  - List
  - Periods (close / reopen)
- **Cost Centers** `[feature:cost_centers]` `[perm:manage_cost_centers]`
  - All cost centers
  - P&L per center
  - Cost analysis
  - Allocation report
- **Budgets** `[feature:budgeting]` `[perm:manage_accounts]`
  - All budgets
  - Variance report

---

## 6. Inventory
- **Products** `[feature:inventory]` `[perm:manage_inventory]`
- **Product Categories** `[feature:inventory]`
- **Stock Movements** `[feature:inventory]`
- **Stock Report** `[feature:inventory]`
- **Low Stock Alerts** `[feature:inventory]`
- **Valuation** `[feature:inventory]`
- **Turnover** `[feature:inventory]`

---

## 7. Fixed Assets
- **Assets** `[feature:fixed_assets]` `[perm:manage_fixed_assets]`
- **Asset Categories** `[feature:fixed_assets]`
- **Depreciation Run** `[feature:fixed_assets]`
- **Disposals** `[feature:fixed_assets]`
- **Reports**
  - Asset register
  - Roll-forward

---

## 8. Payroll & HR
- **Employees** `[feature:payroll]` `[perm:manage_employees]`
- **Payroll Runs** `[feature:payroll]` `[perm:manage_payroll]`
  - All runs
  - Current run
  - Payslips
- **Salary Components** `[feature:payroll]`
- **Attendance** `[feature:payroll]`
  - Daily log
  - Bulk entry
  - Employee summary
- **Leave** `[feature:payroll]`
  - Leave types
  - Leave requests
  - Approvals queue
  - Employee balances
- **Loans & Advances** `[feature:payroll]`
- **Labor Law Tools** `[feature:payroll]`
  - Overtime calculator
  - End-of-service calculator
  - Leave entitlement
  - Wage validator
- **Social Insurance** `[feature:payroll]`
  - Calculator
  - Monthly report
  - Employee registration
  - Current rates

---

## 9. Time & Engagements
- **Timesheets** `[feature:timesheets]` `[perm:manage_timesheets]`
  - My timesheets
  - Team timesheets
  - Pending approval `[perm:approve_timesheets]`
  - Summary
- **Timers** `[feature:timesheets]`
  - Start / stop
  - Running timer
- **Time Billing** `[feature:timesheets]`
  - Preview
  - Generate invoices
- **Engagements** `[perm:manage_engagements]`
  - Dashboard
  - All engagements
  - Time allocation
  - Deliverables
- **Working Papers** `[perm:manage_engagements]`

---

## 10. Tax & Compliance (Egypt)
- **ETA E-Invoicing** `[feature:e_invoice]` `[perm:manage_eta]`
  - Compliance dashboard
  - Documents (prepare / submit / cancel / check status)
  - Bulk retry
  - Bulk status check
  - Reconciliation
  - Settings
- **ETA Item Codes (GPC/EGS)** `[feature:e_invoice]`
  - Catalog
  - Mappings
  - Unmapped lines
  - Auto-assign
  - Bulk import
  - Usage report
- **VAT Returns** `[feature:tax]` `[perm:manage_tax]`
  - Periods
  - Calculate
  - File
  - Record payment
- **Withholding Tax** `[feature:tax]`
  - Certificates
  - Generate / issue / submit
- **Corporate Tax** `[feature:tax]`
  - Annual return
  - Tax adjustments
- **Audit & Compliance** `[feature:audit_log]` `[perm:view_audit]`
  - User access
  - Data changes
  - High-risk events
  - Segregation of duties
  - Summary
  - Export
- **Activity Log**
  - All activity
  - Stats

---

## 11. Reports
- **Financial Statements** `[perm:view_reports]`
  - Trial Balance
  - Income Statement (P&L)
  - Balance Sheet
  - Cash Flow
  - Comparative P&L
  - Comparative Balance Sheet
- **Receivables**
  - AR Aging
  - Client Statement
- **Tax Reports**
  - VAT Return
  - WHT Report
- **Account Ledger** (drill-down)
- **Custom Reports** `[feature:custom_reports]`
  - Saved reports
  - New report (builder)
- **Scheduled Reports** `[feature:reports]` `[perm:manage_reports]`
- **Exports** `[perm:view_reports]`
  - Clients
  - Invoices
  - Journal entries

---

## 12. Clients & CRM
- **Clients** `[feature:clients]` `[perm:manage_clients]`
  - All clients
  - Active
  - Inactive
  - Import CSV
- **Client Messaging** `[feature:clients]`
  - Conversations
  - Templates
  - WhatsApp (Beon.chat)
  - SMS
- **Portal Invitations** `[feature:client_portal]` `[perm:invite_client_portal]`

---

## 13. Integrations
- **E-Commerce** `[perm:manage_integrations]`
  - Dashboard
  - Channels (Shopify / WooCommerce)
  - Orders
  - Bulk convert to invoices
- **Payment Gateways** — Paymob, Fawry (configuration pages)
- **Webhooks (Outbound)** `[perm:manage_settings]`
  - Endpoints
  - Events catalog
  - Delivery history
- **API & Docs** — link to `/v1/docs`

---

## 14. Approvals & Alerts
- **Approval Workflows** `[perm:manage_approvals]`
- **Approvals Queue**
  - Pending
  - History
  - Submit new
- **Alert Rules** `[perm:manage_alerts]`
  - All rules
  - History

---

## 15. Documents
- **Document Library** `[feature:documents]` `[perm:manage_documents]`
  - All documents
  - Bulk upload
  - Archived
  - Storage quota

---

## 16. Imports
- **Import Center** `[perm:manage_clients]`
  - New import
  - Import history
  - Templates (clients / accounts / invoices / bills / opening balances)

---

## 17. Marketing (Public Site)
- **Landing Page** `[perm:manage_landing_page]`
  - Hero, features, testimonials editor
- **Pages** — CMS-style page editor
- **Blog**
  - Posts
  - Categories
  - Tags
  - Featured
- **Contact Submissions**

---

## 18. Settings
- **Company Profile** — name, tax ID, logo, address, bilingual display name.
- **Team** `[perm:manage_team]`
  - Members
  - Invites
  - Roles & permissions
- **Fiscal Year & Periods**
- **Invoice Settings**
- **Aging Reminder Settings**
- **ETA Settings** `[feature:e_invoice]`
- **Notification Preferences**
- **User Preferences** — theme, language (ar/en), shortcuts.
- **Two-Factor Authentication**
- **Device Tokens / Sessions**
- **Subscription & Plan** `[perm:manage_subscription]`
  - Current plan
  - Change plan
  - Usage
  - Usage history
  - Payment history
  - Cancel / renew
- **Webhooks**
- **API Keys** (if exposed)

---

## 19. Onboarding (modal / full-page wizard — not a nav item after completion)
- Progress
- Select template (industry preset)
- Set up Chart of Accounts
- Set up fiscal year
- Import opening balances
- Invite first team member
- Load sample data
- Skip step

---

## 20. Account (top-right avatar menu, not sidebar)
- My Profile
- Change Password
- Preferences (theme, language)
- Notification Preferences
- Two-Factor Setup
- Log out

---

## 21. Global
- **Command Palette** — `Cmd/Ctrl+K` for jump-to-anything (clients, invoices, pages, actions).
- **Notifications Bell** — unread count, mark-read, deep-link.
- **Help & Docs** — link to `/v1/docs`, changelog, support contact.
- **Language Switcher** — ar / en toggle, persists via `/v1/preferences`.
- **Tenant Switcher** (only shown to users belonging to multiple tenants).

---

## Client Portal (separate shell, `[role:portal]`)

> Distinct visual identity from the admin panel. Narrower nav. Client users authenticate via tenant-specific portal login.

- **Dashboard** — total due, paid, overdue.
- **Invoices**
  - Open
  - Paid
  - All
  - Pay invoice (Paymob / Fawry)
  - Download PDF
- **Documents**
  - Shared with me
  - Upload
- **Messages**
  - Inbox
  - Compose
- **Notifications**
- **Profile**

---

## Not in the frontend scope (handled by Filament)

- Super Admin panel (tenant management, plans, subscriptions admin, blog moderation, platform-wide audit). Lives at `/admin` on the Laravel side.
- Platform-level 2FA setup for super admin (Filament `/admin/2fa/setup`).
