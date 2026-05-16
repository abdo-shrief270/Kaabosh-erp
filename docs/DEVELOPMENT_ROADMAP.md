# Muhasebi - Development Roadmap

> Solo Developer + Claude Cowork
> Strategy: Get to first paying customer as fast as possible, then iterate.

---

## The Golden Rule

**Do NOT build everything in the features PDF first.** That document is the vision. This roadmap is the reality. You're one developer — your #1 enemy is building features nobody uses yet. Ship fast, validate, iterate.

---

## Phase 0: Project Setup (Week 1)

**Goal:** A working local dev environment with the entire skeleton wired up.

```
Duration: 3-5 days
Outcome: Empty app runs locally, CI passes, deploy pipeline works
```

- [ ] Initialize Laravel 12 project with PostgreSQL 17 (via Herd)
- [ ] Initialize Nuxt 4 project (separate repo or monorepo — your call)
- [ ] Local dev via Laravel Herd:
  - PHP 8.4 + Nginx (built into Herd)
  - PostgreSQL 17 (Herd's DB service or install via Herd Pro / brew)
  - Redis (Herd's service or `brew install redis`)
  - Local disk for file storage (switch to MinIO/S3 on VPS later)
  - Meilisearch (`brew install meilisearch` or skip for MVP, use PostgreSQL full-text search)
- [ ] Configure Laravel Sanctum for SPA authentication
- [ ] Install & configure core packages:
  - `spatie/laravel-permission` (RBAC)
  - `spatie/laravel-multitenancy` (tenant context)
  - `spatie/laravel-activitylog` (audit trail)
  - `spatie/laravel-medialibrary` (file management)
  - `laravel/horizon` (queue dashboard)
- [ ] Set up basic folder structure (DDD-lite):
  ```
  app/
    Domain/
      Shared/          # Base models, traits, interfaces
      Auth/             # Users, roles, permissions
      Tenant/           # Tenant model, middleware, scoping
      Client/           # Client management
      Accounting/       # GL, JE, CoA
      Billing/          # Invoices, payments
    Http/
      Api/V1/           # API controllers
      Middleware/
    Services/
  ```
- [ ] GitHub repo + GitHub Actions CI (lint + test + build)
- [ ] Basic deploy script to VPS (even if manual for now)
- [ ] Seed data: 1 super admin, 1 test tenant, 1 test user

**Claude Cowork tip:** Ask Claude to scaffold the entire Docker + Laravel + Nuxt setup in one session. It can generate all config files, Dockerfiles, and compose files.

---

## Phase 1: Multi-Tenancy & Auth (Weeks 2-3)

**Goal:** Multiple firms can register, login, and see only their own data. This is the foundation EVERYTHING else depends on.

```
Duration: 7-10 days
Outcome: Tenant registration, login, subdomain routing, data isolation
```

### Backend
- [ ] Tenant model with: name, slug (subdomain), domain, status, settings (JSON)
- [ ] Tenant middleware: resolve tenant from subdomain, set in context
- [ ] PostgreSQL Row-Level Security policies on all tenant tables
- [ ] Global scope trait `BelongsToTenant` — auto-applies `tenant_id` filter
- [ ] User model with tenant relationship, roles (super_admin, admin, accountant, viewer)
- [ ] Registration flow: company name + admin email + password → create tenant + user
- [ ] Login with Sanctum SPA authentication
- [ ] Forgot password / email verification
- [ ] API: `POST /register`, `POST /login`, `POST /logout`, `GET /me`
- [ ] Super admin: separate login route, can list/view all tenants

### Frontend (Nuxt)
- [ ] Auth pages: login, register, forgot password
- [ ] Layout: auth layout (centered card) + dashboard layout (sidebar + topbar)
- [ ] Pinia auth store: login, logout, user state, token management
- [ ] Route middleware: `auth` (must be logged in), `guest` (must not be)
- [ ] Basic dashboard shell (empty, just proves auth works)

### Tests
- [ ] Feature tests: registration creates tenant + user
- [ ] Feature tests: user can only see own tenant data
- [ ] Feature tests: subdomain routing resolves correct tenant

---

## Phase 2: Client Management (Weeks 4-5)

**Goal:** An accounting firm can manage their client list. This is the #1 thing they'll use daily.

```
Duration: 7-10 days
Outcome: Full CRUD for clients with Egyptian-specific fields
```

### Backend
- [ ] Client model with fields:
  - Legal name (AR/EN), trading name
  - Client type (individual, LLC, JSC, etc.)
  - Commercial registration number + date
  - Tax registration number (TIN)
  - Tax file number, VAT registration number
  - Address, phone, email, website
  - Contact persons (JSON or separate table)
  - Classification: tags, industry, risk rating
  - Status: active, inactive, prospect
- [ ] Client API Resource (transformer)
- [ ] API: full CRUD + search + filter + paginate
- [ ] Client import from Excel/CSV (basic)
- [ ] Activity log on all client changes

### Frontend
- [ ] Client list page: searchable table with filters
- [ ] Client create/edit form (multi-step or single page)
- [ ] Client detail page: overview, contact info, documents tab (empty for now)
- [ ] Arabic/English field labels (start i18n early)

### Tests
- [ ] CRUD operations
- [ ] Tenant isolation (client from tenant A not visible to tenant B)
- [ ] Search and filter

---

## Phase 3: Chart of Accounts & General Ledger (Weeks 6-8)

**Goal:** The accounting engine. Double-entry bookkeeping that actually works.

```
Duration: 10-14 days
Outcome: CoA management, journal entries, account balances, trial balance
```

### Backend
- [ ] Account model: code, name_ar, name_en, type (asset/liability/equity/revenue/expense), parent_id, is_active, normal_balance, currency
- [ ] Pre-built Egyptian CoA templates (seed data):
  - Trading company template
  - Services company template
  - General template
- [ ] Onboarding: choose template → auto-create CoA for tenant
- [ ] Journal Entry model: number, date, description, status (draft/posted/reversed), created_by, approved_by
- [ ] Journal Entry Line model: account_id, debit, credit, description, cost_center
- [ ] **Strict double-entry enforcement**: sum(debit) MUST equal sum(credit) — database constraint + validation
- [ ] Posting workflow: draft → post (updates account balances) → reverse (creates reversing entry)
- [ ] Period management: fiscal year, periods, open/close
- [ ] Account balance calculation: opening + sum(debits) - sum(credits) = closing
- [ ] Trial balance report: all accounts with debit/credit/balance columns
- [ ] API: CoA CRUD, JE CRUD, trial balance, account ledger

### Frontend
- [ ] Chart of Accounts: tree view with expand/collapse
- [ ] Account create/edit modal
- [ ] Journal entry form: date, description, multi-line debit/credit rows, auto-balance check
- [ ] Journal entry list with status filters
- [ ] Trial balance page with date range picker
- [ ] Account ledger page (transactions for one account)

### Tests
- [ ] Double-entry balance validation
- [ ] Posting and reversal
- [ ] Trial balance calculation accuracy
- [ ] Period restrictions

**This is the hardest phase.** Take your time. The accounting engine must be bulletproof. Every feature after this depends on it.

---

## Phase 4: Invoicing & Accounts Receivable (Weeks 9-11)

**Goal:** Firms can invoice their clients and track payments. This is how they make money — and how YOU prove value.

```
Duration: 10-12 days
Outcome: Create invoices, record payments, aging report, basic ETA e-invoice
```

### Backend
- [ ] Invoice model: number, client_id, date, due_date, status (draft/sent/paid/overdue/cancelled), subtotal, vat, total, currency, notes
- [ ] Invoice line model: description, quantity, unit_price, vat_rate, amount
- [ ] Auto-numbering: configurable prefix + sequential number per tenant
- [ ] VAT calculation: 14% standard, 0% exempt, configurable per line
- [ ] Payment model: invoice_id, amount, date, method (cash/bank/check), reference
- [ ] Partial payments support
- [ ] Invoice status auto-update: paid when payments >= total
- [ ] Aging analysis: current, 30, 60, 90, 120+ days
- [ ] PDF invoice generation (Arabic/English, branded per tenant)
- [ ] **ETA e-Invoice (basic):** Generate JSON in ETA format, store for manual upload initially
- [ ] Credit note model linked to original invoice
- [ ] Auto-post to GL: debit AR, credit revenue, credit VAT payable

### Frontend
- [ ] Invoice list with status badges and quick filters
- [ ] Invoice create: client select, line items, auto-calculate totals
- [ ] Invoice preview (PDF-style)
- [ ] Record payment modal
- [ ] Aging report page
- [ ] Client statement of account

### Tests
- [ ] Invoice total calculations with VAT
- [ ] Payment recording and status transitions
- [ ] GL posting accuracy
- [ ] Aging bucket calculations

---

## Phase 5: Document Management & File Storage (Weeks 12-13)

**Goal:** Upload, organize, and retrieve documents per client/engagement. VPS storage with MinIO.

```
Duration: 5-7 days
Outcome: File upload/download/organize per client, basic folder structure
```

### Backend
- [ ] MinIO setup on VPS (or just local disk for MVP, switch later)
- [ ] Document model: tenant_id, client_id, name, path, mime_type, size_bytes, hash (sha256), category, uploaded_by, storage_tier
- [ ] Upload API: validate file type/size, generate unique path, store, create DB record
- [ ] Download API: stream file from storage
- [ ] Storage quota check per tenant (plan-based limit)
- [ ] Categories: tax_document, invoice, receipt, contract, financial_statement, correspondence, other
- [ ] Folder structure auto-generation per client: `/tenant_slug/client_id/year/category/`
- [ ] Deduplication: check hash before storing, reference existing file
- [ ] Basic OCR placeholder (skip for MVP, add later)

### Frontend
- [ ] Document library per client (table view with download)
- [ ] Drag-and-drop upload
- [ ] Category filter, search by name
- [ ] Bulk upload

---

## Phase 6: Basic Financial Reports (Weeks 14-15)

**Goal:** The reports that accounting firms deliver to their clients.

```
Duration: 7-10 days
Outcome: Income statement, balance sheet, cash flow (basic), PDF export
```

### Backend
- [ ] Income Statement (P&L): revenue - expenses, grouped by account type, for date range
- [ ] Balance Sheet: assets = liabilities + equity, as of date
- [ ] Cash Flow Statement (indirect method, basic)
- [ ] Comparative reports: current vs prior period
- [ ] Report as JSON API (frontend renders) + PDF export
- [ ] PDF template: professional Arabic/English layout with tenant branding

### Frontend
- [ ] Report selector page
- [ ] Income statement with collapsible sections
- [ ] Balance sheet with standard grouping
- [ ] Date range picker, comparative toggle
- [ ] Export to PDF / Excel buttons

---

## Phase 7: Subscriptions & Billing — You Are Now a SaaS (Weeks 16-18)

**Goal:** Tenants can subscribe, pay, and you generate recurring revenue.

```
Duration: 10-12 days
Outcome: Plans, checkout, recurring billing, tenant self-service
```

### Backend
- [ ] Plan model: name, slug, price_monthly, price_annual, limits (users, clients, storage, etc.), features (JSON)
- [ ] Seed plans: Free Trial (14 days), Starter, Professional, Enterprise
- [ ] Subscription model: tenant_id, plan_id, status, current_period_start, current_period_end, payment_method
- [ ] Paymob integration (Egyptian market):
  - Card payments
  - Fawry (cash collection points)
  - Mobile wallets (Vodafone Cash, Orange)
- [ ] Webhook handler for payment events
- [ ] Subscription lifecycle: trial → active → past_due → cancelled → expired
- [ ] Feature gating middleware: check plan limits before allowing actions
- [ ] Usage tracking: count users, clients, storage per tenant
- [ ] Invoice generation for subscription payments

### Frontend (Nuxt)
- [ ] Pricing page (public)
- [ ] Plan selection during registration
- [ ] Billing settings page (current plan, payment method, invoices)
- [ ] Upgrade/downgrade flow
- [ ] Usage dashboard (how much of your plan you're using)

### Super Admin
- [ ] Revenue dashboard: MRR, subscriber count, churn
- [ ] Tenant subscription management

---

## Phase 8: Tenant Onboarding & Polish (Weeks 19-20)

**Goal:** A new firm signs up and is productive within 15 minutes.

```
Duration: 5-7 days
Outcome: Guided setup wizard, sample data, help tooltips
```

- [ ] Onboarding wizard: Company details → CoA template → Invite team → Connect bank (skip) → Done
- [ ] Sample data option: pre-loaded demo clients + transactions for exploration
- [ ] Dashboard with real KPIs: client count, outstanding receivables, revenue this month
- [ ] Help tooltips on key screens
- [ ] Notification system (in-app): welcome message, setup reminders
- [ ] Email templates: welcome, invite teammate, invoice sent, payment received

---

## Phase 9: ETA E-Invoice Full Integration (Weeks 21-23)

**Goal:** Full electronic invoice submission to Egyptian Tax Authority. This is a KILLER feature that firms will pay for.

```
Duration: 10-12 days
Outcome: Auto-submit invoices to ETA, track status, handle rejections
```

- [ ] ETA API integration: authentication, token management
- [ ] Digital signing: HSM/USB token integration (or certificate-based for MVP)
- [ ] Invoice → ETA JSON transformation (all required fields)
- [ ] Real-time submission to ETA API
- [ ] Status tracking: submitted → accepted/rejected, error details
- [ ] UUID retrieval and QR code generation
- [ ] E-receipt support (B2C)
- [ ] ETA code management: item codes (GS1/EGS), activity codes
- [ ] Credit note / debit note submission
- [ ] Reconciliation: ETA records vs internal records
- [ ] ETA settings page: credentials, certificates, activity codes

---

## Phase 10: Team, Time Tracking & Basic Payroll (Weeks 24-27)

**Goal:** Firms can manage staff, track billable hours, and run basic payroll.

```
Duration: 12-15 days
Outcome: Staff management, timesheets, billing from time, salary processing
```

- [ ] Team management: invite users, assign roles, manage permissions
- [ ] Timesheet: daily entry with client + task + hours + billable flag
- [ ] Timer (start/stop)
- [ ] Timesheet approval workflow
- [ ] Billing from time: convert approved hours to invoice
- [ ] Basic payroll: employee master, salary structure, monthly payroll run
- [ ] Egyptian social insurance calculation (basic)
- [ ] Egyptian income tax brackets
- [ ] Pay slip PDF generation

---

## Phase 11: Client Portal (Weeks 28-30)

**Goal:** Clients of the accounting firms can login and see their stuff.

```
Duration: 10-12 days
Outcome: Separate portal where clients view invoices, upload docs, message firm
```

- [ ] Client user model: separate auth, invited by firm
- [ ] Client dashboard: outstanding balance, recent invoices, tax deadlines
- [ ] Invoice history + online payment (Paymob integration)
- [ ] Document upload (respond to document requests)
- [ ] Document library (view shared documents)
- [ ] Secure messaging with the firm
- [ ] Tax filing status view

---

## Phase 12: Mobile App MVP — Flutter (Weeks 31-34)

**Goal:** Quick access on mobile for both firm staff and clients.

```
Duration: 12-15 days
Outcome: Flutter app with core features for iOS + Android
```

- [ ] Auth flow (login, biometric)
- [ ] Dashboard (KPIs)
- [ ] Client list + detail
- [ ] Quick time entry
- [ ] Quick expense capture (camera → receipt → OCR later)
- [ ] Push notifications
- [ ] Client portal features (for client app variant)

---

## Beyond: Post-Launch Iteration (Months 9+)

Only build these AFTER you have paying customers and validated demand:

- Accounts Payable & vendor management
- Fixed asset management & depreciation
- Bank reconciliation (with bank feed if API available)
- Engagement/case management with working papers
- Advanced tax modules (corporate tax computation, WHT certificates)
- Budgeting & forecasting
- Audit management
- Multi-branch & cost center
- CRM & business development pipeline
- AI features (auto-categorization, anomaly detection)
- Super admin: investor management & profit sharing
- White-labeling
- Marketplace add-ons

---

## Realistic Timeline Summary

| Phase | What | Duration | Cumulative |
|-------|------|----------|------------|
| 0 | Project Setup | 1 week | Week 1 |
| 1 | Multi-Tenancy & Auth | 2 weeks | Week 3 |
| 2 | Client Management | 2 weeks | Week 5 |
| 3 | Chart of Accounts & GL | 3 weeks | Week 8 |
| 4 | Invoicing & AR | 3 weeks | Week 11 |
| 5 | Document Management | 2 weeks | Week 13 |
| 6 | Financial Reports | 2 weeks | Week 15 |
| 7 | Subscriptions & Billing | 3 weeks | Week 18 |
| 8 | Onboarding & Polish | 2 weeks | Week 20 |
| **MVP LAUNCH** | **Go live, get first customers** | | **~5 months** |
| 9 | ETA E-Invoice | 3 weeks | Week 23 |
| 10 | Team & Payroll | 4 weeks | Week 27 |
| 11 | Client Portal | 3 weeks | Week 30 |
| 12 | Mobile App | 4 weeks | Week 34 |
| **V2 COMPLETE** | **Full product** | | **~8 months** |

---

## Working with Claude Cowork — Tips

1. **One module per session.** Don't try to build everything in one conversation. Start a session with: "Let's build the Client Management module. Here's the schema I want..." and let Claude scaffold migrations, models, controllers, tests, and frontend.

2. **Always start with the migration + model.** The database is the source of truth. Get the schema right first, then build up.

3. **Ask Claude to write tests WITH the code.** Not after. "Build the JournalEntry model with full CRUD API and feature tests." Tests catch bugs before they compound.

4. **Use Claude for the boring stuff.** Form validation, API resources, table components, permission setup — Claude is fastest at repetitive patterns. Save your brain for business logic.

5. **Review everything Claude generates.** Especially financial calculations. Double-check debit/credit logic, tax math, and rounding. Accounting software has zero tolerance for math errors.

6. **Commit after every working feature.** Don't accumulate huge uncommitted changes. Small, working commits.

7. **Deploy early.** Get a staging VPS running by Phase 1. Deploy after every phase. Catch deployment issues early, not at launch.

---

## Local Dev Setup (Day 1) — Laravel Herd

You already have Herd. Here's what you need:

```
Laravel Herd (already installed)
├── PHP 8.4           ✅ Built into Herd
├── Nginx             ✅ Built into Herd
├── Node.js 22 LTS    ✅ Built into Herd (or install via nvm)
├── Composer           ✅ Built into Herd
├── PostgreSQL 17      → Herd Pro DB service, or: brew install postgresql@17
├── Redis              → Herd Pro service, or: brew install redis
└── Meilisearch        → Skip for MVP (use PostgreSQL full-text search)

Local domains (via Herd):
- muhasebi.test          → Laravel API
- app.muhasebi.test      → Nuxt frontend (proxy via Herd or run on :3000)
- *.muhasebi.test        → Tenant subdomains (Herd supports wildcard)
```

File storage for MVP: just use Laravel's `local` disk (`storage/app/`).
Switch to MinIO/S3 on VPS later — it's a one-line config change in `.env`.

## VPS Setup (When Ready to Deploy — Phase 7+)

```
Hetzner CPX31 (4 vCPU, 8GB RAM, 160GB SSD) — ~€15/mo

Install:
- Ubuntu 24.04 LTS
- Caddy (auto-SSL, wildcard subdomain support)
- PHP 8.4 + PHP-FPM
- PostgreSQL 17
- Redis 8
- Node.js 22 LTS (for Nuxt SSR)
- Supervisor (for queue workers + Horizon)

Domain:
- muhasebi.com → VPS IP
- *.muhasebi.com → VPS IP (wildcard for tenants)
```

No Docker on production either — bare metal is simpler and faster for a solo dev.
One server, under $20/month. Scale when you need to.
