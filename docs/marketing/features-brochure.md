# muhasebi

### برنامج المحاسبة السحابي للسوق المصري
### Cloud Accounting for the Egyptian Market

---

*A bilingual, ETA-ready, multi-tenant accounting platform built for Egyptian SMEs, accounting firms, and finance teams.*

> **Visual suggestion for cover:** a clean, modern dashboard screenshot layered over a subtle Arabic calligraphy motif in teal. On the right, a smartphone preview shows an invoice in Arabic with a WhatsApp reminder bubble.

---

## The Problem

Running a business in Egypt means juggling ledgers, ETA submissions, labor law, and client chasing all at once. Most SMEs still rely on spreadsheets, paper receipts, and fragmented tools that don't talk to each other. The cost is not just time — it's missed deadlines, penalties, and cashflow surprises.

Compliance has become non-negotiable. The Egyptian Tax Authority's (ETA) e-invoicing mandate, monthly VAT returns, quarterly withholding tax certificates, annual corporate tax filing — each one has its own portal, its own deadline, and its own risk of rejection. Payroll under Egyptian Labor Law 12/2003 adds another layer: social insurance brackets, end-of-service gratuity, overtime multipliers, annual leave entitlement. Getting any of these wrong is expensive.

Meanwhile, business owners are losing sleep over money they've already earned. Invoices sit unpaid for 60, 90, even 120 days. Clients don't answer the phone. The accountant's monthly report arrives two weeks after it's useful. There has to be a better way — one built for Egypt, in Arabic, with the right calculations baked in from day one.

---

## The Solution

**muhasebi** is a cloud accounting platform built from the ground up for the Egyptian market. It combines double-entry bookkeeping, ETA e-invoicing, Egyptian payroll, multi-currency operations, client portals, and WhatsApp/SMS collections into one Arabic-first, RTL-aware workspace — with fair per-tenant pricing and role-based access that scales from a solo freelancer to a 200-employee firm. You focus on the business; muhasebi handles the plumbing.

---

## Feature Tours

### 1. Accounting & Bookkeeping

> 🧾 **The general ledger that keeps itself tidy.**

- **Hierarchical Chart of Accounts** in Arabic and English, with parent/child relationships and balance rollups.
- **Journal entries** with debit/credit validation, draft/post workflow, reversal, and full audit trail.
- **Recurring journal entries** — set a monthly rent accrual or depreciation booking once, and it posts on schedule.
- **AI-assisted account suggestions** learn from your past entries to speed up data capture.
- **Fiscal years and periods** with soft close, reopen, and period locking so nothing slips past year-end.
- **Bank reconciliation** with statement import (CSV/OFX), smart auto-match, manual match, and learning categorization rules that get smarter with every reconciliation.
- **Multi-currency** with live FX rates, rate history, and automated FX revaluation postings for open balances.
- **Opening balance import** and CSV account import so migrating from your old system takes hours, not weeks.

> 💡 **Tip:** The bank reconciliation engine suggests matches with confidence scores, then learns from your corrections. By month three, most of your statement lines auto-match.

---

### 2. Invoicing & Cashflow

> 💳 **Get paid faster, without chasing.**

- **Beautiful Arabic/English invoices** with your logo, terms, tax breakdown, and QR code for digital payment.
- **Recurring invoices** for retainers and subscriptions — set the schedule, we generate and send.
- **Credit notes** and cancellations with automatic GL reversal.
- **Online payment collection** via Paymob and Fawry, directly from the PDF or the client portal.
- **Partial payments** tracked line-by-line, with automatic write-off and escalation workflows.
- **Collections cockpit** — see every overdue invoice, log calls/emails/SMS actions, and measure collection effectiveness.
- **Smart aging reminders** that email or WhatsApp the client 3, 7, 14, and 30 days past due — all configurable, all automatic.
- **Client portal** where customers see their statements, download PDFs, and pay online 24/7.
- **Pre-check validation** catches duplicate invoice numbers, missing ETA codes, and credit-limit overruns before you save.

> 💡 **Tip:** Turn on aging reminders with WhatsApp fallback. Customers who ignore emails almost never ignore a WhatsApp message.

---

### 3. Payables & Expenses

> 📥 **Know what you owe, approve with confidence.**

- **Vendor master** with tax IDs, payment terms, default expense accounts, and running statements.
- **Bill capture** with approval routing, partial payments, and void/cancel workflows.
- **Vendor aging** report so you never miss a due date or a prompt-payment discount.
- **Expense categories** with colors and analytics.
- **Employee expense reports** — staff submit receipts, managers approve, finance reimburses, all in-app.
- **Bulk submit and bulk approve** for high-volume weeks.
- **Receipt attachments** stored securely against every expense line.
- **Cost center tagging** on every bill and expense for departmental P&L.

---

### 4. Inventory & Fixed Assets

> 📦 **Stock you can count on.**

**Inventory**
- Product catalog with SKUs, categories, units of measure, and reorder levels.
- Stock movements (in/out/adjustment) with full traceability.
- Low-stock alerts, turnover reports, and valuation (FIFO / LIFO / Weighted Average).
- Per-product movement history and current on-hand levels.

**Fixed Assets**
- Asset register by category with useful-life, salvage rate, and depreciation method.
- Automatic monthly depreciation runs with journal postings.
- Depreciation schedules, asset register reports, and roll-forward schedules.
- Disposal workflow with gain/loss calculation to the GL.

---

### 5. Payroll & HR — Built for Egyptian Labor Law

> 👥 **Payroll that knows Law 12/2003 by heart.**

- **Employee master** with Egyptian-specific fields (national ID, social insurance number, bank account).
- **Payroll runs** by month, with calculate → approve → mark-paid workflow.
- **Salary components** — fixed, variable, taxable, non-taxable, benefits-in-kind.
- **Payslip PDFs** in Arabic and English, downloadable by employee.
- **Attendance** with bulk entry, check-in/check-out, and monthly summaries.
- **Leave management** — annual, casual, sick, emergency; requests, approvals, and running balances.
- **Employee loans** and installment tracking, with automatic payroll deduction.
- **Egyptian Labor Law engine**:
  - Overtime calculation (regular, weekend, public holiday multipliers).
  - End-of-service gratuity calculation per Law 12/2003.
  - Leave entitlement by seniority bracket.
  - Minimum wage validation.
- **Social Insurance** — monthly reports, bracket calculation, employer/employee split, registration helpers.

> 💡 **Tip:** The social insurance module auto-updates when Law 148/2019 brackets change, so you don't have to track the circulars.

---

### 6. Tax & Compliance

> 🛡️ **ETA-ready from day one.**

**VAT**
- Automatic VAT calculation on invoices and bills at standard, zero, and exempt rates.
- Monthly **VAT return** generation with PDF for tax office submission.
- Full input/output VAT reconciliation.

**Withholding Tax (WHT)**
- Generate WHT certificates per vendor and per transaction.
- Issue, submit, and track certificate status.
- WHT summary reports for quarterly filing.

**Corporate Tax**
- Annual corporate tax calculation with manual adjustments, timing differences, and permanent differences.
- File-and-pay workflow with payment recording.

**ETA E-Invoicing**
- Full Egyptian Tax Authority e-invoice integration — prepare, submit, and reconcile.
- Document status tracking (submitted, accepted, rejected) with government timestamp.
- **Item code management** — search, bulk-assign, auto-suggest, and bulk-import the ETA item-code taxonomy.
- **Unmapped line detection** catches invoices that aren't ready for ETA before you send them.
- **Compliance dashboard** showing submission success rate, pending documents, and rejection reasons.
- Bulk retry and bulk status-check for high-volume days.

> 💡 **Tip:** The ETA compliance dashboard flags unmapped product lines in red. Fix them once, and the auto-assign rule handles every future invoice.

---

### 7. Reporting & Business Intelligence

> 📊 **The numbers you need, when you need them.**

**Standard Financial Reports**
- Trial balance, general ledger, account ledger.
- Income statement, balance sheet, cash flow statement — with comparative periods.
- Aging (AR and AP), client statements, vendor statements.
- All reports exportable to PDF with your branding.

**Tax Reports**
- VAT return, WHT report — both screen and PDF.
- Corporate tax workings.

**Executive Dashboard**
- Revenue analysis, cashflow projection, profitability by period.
- KPIs for revenue growth, gross margin, days sales outstanding.
- Period-over-period comparison.

**Custom Reports**
- Build your own reports with a drag-and-drop column selector, filters, grouping, and sorting. Save, run on demand, or schedule.

**Scheduled Reports**
- Email any report to stakeholders on a daily, weekly, or monthly cadence — fully hands-off.

**Anomaly Detection**
- Duplicate entries, unusual amounts, missing document sequences, weekend postings — all flagged automatically.

**Exports**
- One-click CSV export of clients, invoices, and journal entries for backup or external analysis.

---

### 8. Automation & Integrations

> 🔌 **muhasebi plays well with others.**

- **E-commerce sync** — connect Shopify, WooCommerce, and more. Orders auto-import and convert to invoices.
- **Bank connections** — supported-format import, balance sync, statement import.
- **Payment gateways** — Paymob and Fawry, with live webhook reconciliation.
- **WhatsApp & SMS via Beon.chat** — send invoices, reminders, and two-way conversations from inside muhasebi.
- **Webhooks** — subscribe any external system to events like `invoice.created`, `payment.received`, `expense.approved`. Includes retry, signature verification, and delivery logs.
- **CSV/XLSX imports** for clients, accounts, invoices, bills, and opening balances with validation and error reports.
- **Public OpenAPI** for building your own integrations.

---

### 9. Client Portal & Professional Services

> 🤝 **For accounting firms: your clients, their portal, your brand.**

**Client Portal** (for every muhasebi tenant)
- Clients log in to view invoices, pay online, download PDFs, and exchange secure documents.
- Two-way messaging thread with read receipts.
- In-portal notifications for new invoices, statements, and messages.

**Engagements & Working Papers** (for accounting firms)
- Track engagements per client with service type, start/end dates, and budget vs. actual cost.
- Deliverables with due dates and completion workflow.
- Working papers — upload, review, sign-off, and archive by reference number.
- Time allocation analytics per engagement.

**Timesheets & Time-Billing**
- Weekly timesheets with employee, client, and project tagging.
- In-browser live timer for ad-hoc work.
- Approval workflow (submit → approve → bill).
- Generate invoices directly from approved timesheets at your billable rate.

---

### 10. Security & Multi-Tenancy

> 🔒 **Built for peace of mind.**

- **Multi-tenant isolation** — every tenant's data is fully segregated; one customer can never see another's ledger.
- **Two-factor authentication (TOTP)** with backup codes, enforced by policy for sensitive roles.
- **Role-Based Access Control (RBAC)** — over 40 fine-grained permissions (`manage_invoices`, `post_journal_entries`, `view_reports`, `manage_eta`, etc.) grouped into roles you can customize per tenant.
- **Feature gating** — plans are enforced by `feature:*` middleware on the backend. Upgrades take effect instantly.
- **Activity logs** — every create/update/delete captured with user, IP, and before/after values.
- **Audit compliance dashboard** — user access reports, change reports, high-risk actions, and segregation-of-duties analysis. Export for your external auditor.
- **Alert rules** — set thresholds (e.g. "notify me when any AR balance exceeds 100k EGP") and get emailed automatically.
- **Approval workflows** — define multi-step approval chains for bills, expenses, journal entries, and more.
- **Device tokens** for managing sessions across phone, laptop, and tablet.
- **Rate limiting** on sensitive endpoints (login, payments, 2FA).

---

## Why muhasebi

> **سبعة أسباب — Seven reasons to choose muhasebi.**

1. **Arabic-first, not Arabic-later.** Every screen, every report, every email is native bilingual with proper RTL layout — not a translation bolted on.
2. **ETA-ready from day one.** Full e-invoicing integration, item-code management, and compliance dashboard — no plugins, no third-party middleware.
3. **Built for Egypt.** Law 12/2003 payroll, Law 148/2019 social insurance, EGP-first multi-currency, Egyptian fiscal-year conventions, Paymob and Fawry payments, Beon.chat WhatsApp.
4. **Multi-tenant for accounting firms.** One login, many client ledgers — with full isolation, engagement tracking, and working papers.
5. **Mobile-friendly.** Responsive web UI and device-token-based authentication means your accountant can approve a bill from their phone on the way to a client.
6. **Fair pricing in EGP.** No dollar pricing, no surprise overages — transparent per-tenant plans.
7. **Your data, your audit trail.** Every change logged, exportable, and inspectable. You're always ready for an auditor or an ETA review.

---

## Plans Overview

muhasebi is offered in four tiers. Exact pricing is available on our pricing page — all plans are billed in EGP with monthly, quarterly, and annual options.

### Starter
For freelancers and micro-businesses.
- Core accounting, invoicing, basic reporting, client portal for up to a handful of clients.
- VAT support.

### Growth
For small and mid-sized businesses.
- Everything in Starter, plus payroll, inventory, fixed assets, bank reconciliation, collections, e-commerce sync, expense reports, WhatsApp/SMS messaging.
- Full ETA e-invoicing.

### Pro
For mid-market and accounting firms.
- Everything in Growth, plus engagements, working papers, timesheets, custom reports, scheduled reports, approval workflows, alert rules, audit compliance dashboard, webhook endpoints, anomaly detection.

### Enterprise
For large organizations.
- Everything in Pro, plus dedicated onboarding, priority support, SLA, and custom integration assistance.

> 💡 **Tip:** Features are enabled via backend `feature:*` gating. When you upgrade, the new modules light up in your menu immediately — no redeployment, no data migration.

---

## Getting Started

Three steps. About twenty minutes to a working ledger.

### 1. Sign Up
Create your tenant at **muhasebi.app** with your company name, email, and phone. We send a confirmation and provision your workspace in seconds.

### 2. Onboarding Wizard
Our guided wizard walks you through:
- Choosing a **Chart of Accounts template** (general SME, retail, services, manufacturing) — or uploading your own.
- Setting up your **fiscal year**.
- Importing **opening balances** from a CSV or your old system.
- Optionally loading **sample data** to explore the product first.

### 3. Invite Your Team
Add accountants, salespeople, and managers. Assign them pre-built roles (Owner, Accountant, Manager, Sales, Read-Only) or build your own. Enable 2FA for anyone touching money.

That's it. Create your first invoice, send it, watch it get paid.

---

## Contact

> **Ready to stop fighting your spreadsheets?**

- **Web:** muhasebi.app
- **Email:** hello@muhasebi.app
- **Phone / WhatsApp:** [your number here]
- **Address:** [your Cairo office here]

Book a 30-minute guided demo and we'll migrate your chart of accounts and opening balances for free.

---

*muhasebi — because your business deserves an accountant that never sleeps.*

*محاسبي — لأن عملك يستحق محاسبًا لا ينام.*
