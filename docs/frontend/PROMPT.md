# muhasebi — Frontend Build Brief

> Paste this file into your frontend coding agent (v0, Cursor, Claude Code, etc.). It is self-contained: everything the agent needs to scaffold the entire tenant admin panel plus a client-facing portal for **muhasebi**, a multi-tenant accounting SaaS for the Egyptian market.

---

## 1. Product one-paragraph brief

**muhasebi** (Arabic: محاسبي, "my accountant") is a multi-tenant accounting and compliance SaaS built for the Egyptian market: SMEs, accounting firms, bookkeepers, and their clients. It covers the full accounting cycle (chart of accounts, journal entries, AR/AP, bank reconciliation, payroll, fixed assets, inventory, cost accounting, budgeting, financial reporting) plus Egypt-specific compliance: **ETA e-invoicing**, VAT / WHT / Corporate Tax returns, Law 12/2003 labor calculators, and social insurance. It is **bilingual Arabic-English, RTL-first**, and includes a public marketing site, a **client portal**, and **Shopify/WooCommerce** plus **WhatsApp/SMS (Beon.chat)** integrations. Target impression: a modern Ramp/Linear-class product — fast, calm, confident — explicitly *not* another Odoo/SAP clone. EGP is the default currency.

---

## 2. Users & roles

| Role | Lives in | Scope |
|---|---|---|
| **Super Admin** | Filament (`/admin`) — **NOT your concern** | Tenant/plan/platform management. Skip. |
| **Tenant Admin** | This app | Full tenant access: billing, team, settings, all modules. |
| **Accountant** | This app | Full accounting: COA, JE, tax, reports, bank rec. Limited team/billing. |
| **Manager** | This app | Approvals, reports, dashboards. Cannot post JE. |
| **Employee** | This app | Own timesheets, expenses, leave, attendance. Read-only on most dashboards. |
| **Client** | Client Portal (separate shell) | Own invoices, documents, messages. Can pay invoices online. |

Gate UI by **permissions from `/v1/me`** (RBAC via Spatie on the backend — just read the array). Gate entire modules by **plan features from `/v1/subscription`** (`feature:*` middleware on the backend). If the user lacks the permission, hide/disable controls; if the plan lacks the feature, hide the nav entry and show a soft-paywall on direct URL hit.

---

## 3. Tech stack (opinionated, mandatory)

- **Next.js 15** App Router, React 19, **TypeScript strict mode**.
- **Tailwind CSS v4** (new engine, CSS vars).
- **shadcn/ui** as the component primitive layer. Do not hand-roll primitives.
- **TanStack Query v5** — all server state. Defaults: `staleTime: 30s`, `gcTime: 5m`, `refetchOnWindowFocus: false` for heavy tables.
- **TanStack Table v8** — every list/resource screen.
- **react-hook-form** + **Zod** — all forms. Share Zod schemas with the API client for end-to-end types.
- **Recharts** — financial charts (P&L trend, cash flow, dashboards).
- **lucide-react** — icons, one icon set only.
- **date-fns** + `date-fns/locale/ar-EG` — date formatting.
- **@tanstack/react-virtual** — virtualize ledger and long lists (>500 rows).
- **Zustand** — sparingly, for local UI state that crosses components (sidebar, theme, locale). Never for server data.
- **next-intl** (or `next-i18next`) — i18n.
- **react-pdf-viewer** or `<iframe>` — PDF previews (invoices, reports).
- **sonner** — toasts.
- **cmdk** — command palette.
- **vaul** — mobile drawers.

Why this stack: it's the modern, typed, batteries-included default. TanStack Query + Zod give us end-to-end type safety against the Laravel API without GraphQL. shadcn prevents a rebuild-from-scratch trap. Next.js App Router gives route-level code splitting for free.

**No Redux. No Material UI. No Ant Design. No custom UI framework.**

---

## 4. Design system

### Brand tone
Professional, trustworthy, fast. References: **Ramp** (data density + elegance), **Notion** (content clarity), **Linear** (motion + keyboard-first), **Stripe Dashboard** (financial polish). Anti-references: Odoo, SAP, Zoho — cluttered, visually loud, modal-heavy.

### Color
- **Base**: neutral grayscale. Tailwind `neutral` in light mode, `zinc` in dark mode, with carefully tuned CSS variables.
- **Accent (primary)**: **Deep Teal** — `oklch(0.52 0.13 195)` (approx. `#0d7a82`). Egyptian context nods to turquoise/Nile heritage without being kitsch. Works in dark mode as `oklch(0.72 0.12 190)`.
- **Semantic**: success (emerald 600/400), warning (amber 500/400), danger (rose 600/400), info (sky 600/400).
- **Money colors**: positive = default text; **negative = rose 600 (light) / rose 400 (dark)**; zero = muted. Never use green for positive — it's visually noisy. Parentheses `(EGP 1,234.00)` for negative in reports (accountant convention), plain minus elsewhere.
- **Contrast**: WCAG 2.2 AA minimum. Primary button against background: 4.5:1+. Verify dark mode independently.
- **Dark mode**: native, not bolted on. Use CSS variables throughout. Toggle persisted via `/v1/preferences`.

### Typography
- **Latin**: `Inter` variable font, fallback to system UI stack.
- **Arabic**: `Cairo` (primary) or `IBM Plex Arabic` (fallback). Load via `next/font`.
- **Monospace / numerics**: `JetBrains Mono` only for code; for money use `Inter` with `font-variant-numeric: tabular-nums lining-nums`.
- **Scale**: 12, 13, 14 (base), 16, 18, 20, 24, 30, 36, 48.
- **Line heights**: body 1.5, headings 1.2, numeric columns 1.3.

### Density & spacing
- 14px base body, 13px in dense tables.
- 4/8px spacing grid. Never use odd values.
- Table row height: 36px dense, 44px default, 52px comfortable. User-toggleable.
- Card padding: 16px mobile, 20–24px desktop.

### Motion
- 120–200ms easings. `cubic-bezier(0.2, 0.8, 0.2, 1)` for enter, `cubic-bezier(0.4, 0, 0.2, 1)` for exit.
- Skeletons fade in after 200ms (prevent flash on fast requests).
- No parallax, no scroll-linked animations, no confetti. Subtle hover lifts only.
- Respect `prefers-reduced-motion`.

### Iconography
`lucide-react`, 16px in dense UI, 20px in primary buttons, 24px in empty states. Stroke 1.5. Mirror directional icons (chevrons, arrows) in RTL via `rtl:scale-x-[-1]`.

### Money formatting
- **Default currency: EGP** (`ج.م` in Arabic, `EGP` or `E£` in English).
- Use `Intl.NumberFormat(locale, { style: 'currency', currency: tx.currency })`.
- Arabic locale: `ar-EG`. English: `en-EG` (preferred over `en-US` — keeps EGP-aware thousands).
- Always tabular-nums in tables.
- Negative: red, leading `-` in LTR, trailing `-` in RTL (the formatter handles this — don't hand-roll).
- Report mode: parentheses for negatives, toggleable in user preferences.

### Components checklist (all via shadcn primitives)
Button, Input, Label, Select (Combobox), Textarea, Checkbox, RadioGroup, Switch, Slider, DatePicker, DateRangePicker, NumberInput (tabular-nums), Dialog, Sheet (drawer), Tabs, Accordion, Popover, Tooltip, HoverCard, DropdownMenu, ContextMenu, Command (palette), Toast (sonner), Skeleton, Badge, Avatar, Card, Table (wrap TanStack), Pagination, Breadcrumb, Alert, AlertDialog, EmptyState, ErrorState, Stat card, DataCard, Metric tile, FilePicker, DropZone.

---

## 5. i18n & RTL

- **Languages**: Arabic (`ar`) default for Egyptian tenants, English (`en`) available. Per-user preference from `/v1/preferences`; persists across devices.
- **Direction**: `<html dir="rtl">` when `ar`. Use **logical CSS properties everywhere**: `ps-*` / `pe-*` (Tailwind), `margin-inline-start`, `inset-inline-start`, `border-inline-start`. Forbid `pl-*` / `pr-*` in authored CSS.
- **Mirroring**: icons that imply direction (arrow-left, chevron-right) must flip. `rtl:-scale-x-100` helper class.
- **Dates**: Gregorian by default. Expose a user-pref toggle for Hijri display on invoice/report rendering (ETA uses Gregorian, stay aware).
- **Numbers**: Western digits (0-9) by default; optional preference for Eastern Arabic numerals (٠-٩). Never mix within a screen.
- **Currency**: per-transaction currency wins; display in user locale.
- **Translations**: co-locate keys; nested by feature (`invoices.create.lineItems`). Never concatenate translated strings — use ICU message syntax.
- **Mixed content**: always wrap Latin-script fragments inside Arabic text with `<bdi>`.
- **Test every screen in both directions.** A screen that is not deliberately tested in RTL is broken in RTL.

---

## 6. Information architecture

See `docs/frontend/IA.md` for the full nested sidebar tree. High-level groupings:

1. Dashboard
2. Sales (Invoices, Recurring, Payments, Collections, Aging, Settings)
3. Purchases (Vendors, Bills, Expenses, Expense Reports)
4. Banking (Accounts, Connections, Reconciliations, FX, Currencies)
5. Accounting (COA, Journal, Recurring, Fiscal, Cost Centers, Budgets)
6. Inventory (Products, Movements, Reports)
7. Fixed Assets (Assets, Categories, Depreciation, Disposals)
8. Payroll & HR (Employees, Runs, Components, Attendance, Leave, Loans, Labor Law, Social Insurance)
9. Time & Engagements (Timesheets, Timers, Billing, Engagements, Working Papers)
10. Tax & Compliance (ETA, VAT, WHT, Corporate Tax, Adjustments, Audit)
11. Reports (Financial, Receivables, Tax, Custom, Scheduled, Exports)
12. Clients & CRM (Clients, Messaging, Portal Invitations)
13. Integrations (E-commerce, Gateways, Webhooks, API Docs)
14. Approvals & Alerts
15. Documents
16. Imports
17. Marketing (Landing, Pages, Blog, Contacts)
18. Settings (Company, Team, Fiscal, 2FA, Subscription, Preferences)

### Nav shell
- **Left sidebar**, collapsible to icons-only (width 240 → 64). State persisted per user.
- **Groups** open/close with persisted state. Arrow keys navigate when focused.
- **Global Command Palette** — `Cmd+K` / `Ctrl+K` — jumps to any page, searches clients/invoices/accounts, runs quick actions (Create invoice, New JE, Start timer). Use `cmdk`.
- **Top bar**: tenant switcher (if multi-tenant user), global search, notifications bell (unread badge from `/v1/notifications/unread-count`), help, avatar menu.
- **Mobile**: collapse sidebar into off-canvas sheet. Primary actions become a bottom action bar.

---

## 7. Page patterns

### Dashboard
- 3–4 stat cards at top: revenue (period), outstanding AR, cash balance, overdue invoices count.
- Secondary: revenue trend line (Recharts area), invoice status breakdown (donut), upcoming bills.
- Quick actions bar: **New invoice**, **Record payment**, **New JE**, **Start timer**.
- Every card is deep-linkable and click-through.

### Resource list
- **TanStack Table** with server-side pagination, sorting, filtering.
- Sticky header, sticky first column for wide tables (RTL: sticky *last* column in RTL mode).
- **Filters**: inline faceted filters above the table (date range, status, client, amount range). Filter chips summarize active filters with remove-X.
- **Column controls**: show/hide, reorder, resize. Persist per-user per-table.
- **Bulk actions**: checkbox column, bulk toolbar fades in when rows selected (N selected · Delete · Export · Approve).
- **Density toggle**.
- **CSV export** button wires to the relevant `/v1/export/*` endpoint.
- **Empty state**: illustration + one-line explanation + primary CTA + secondary "Learn more" link.
- **Skeleton** rows while loading.
- **Row actions**: overflow menu on hover + keyboard (`⋯`).

### Resource detail
- Header: name/number, status badge(s), primary actions (Edit, Delete, plus context-specific: Post, Send, Approve, Cancel).
- **Tabs** for sub-sections (Overview, Activity, Related, Documents).
- **Activity feed** pulls from `/v1/activity-log` filtered by entity.
- Right rail or bottom section for related resources (e.g., invoice → payments, client → invoices).

### Create / Edit forms
- **Modal/Sheet for quick creates** (<5 fields, atomic).
- **Full-page form for complex creates** (invoices, journal entries, payroll). Use multi-column layouts on wide screens; collapse to stacked on mobile.
- **Autosave drafts** every 30s for complex forms (invoices, JE). Badge: "Saved 3s ago".
- **Validation**: inline (below each field) + summary at top if submission fails. Use Laravel's `{ message, errors: { field: ["..."] } }` shape; map via Zod.
- **Keyboard shortcuts**: `Cmd+S` saves, `Esc` cancels (with "unsaved changes" prompt).
- **Confirm destructive**: AlertDialog with typed-confirmation for irreversible deletes (`type DELETE to confirm`).

### State placeholders (every list & detail needs all four)
1. **Loading** — skeleton matching real shape, not spinners.
2. **Empty** — illustrated, with primary CTA.
3. **Error** — icon + message + Retry + "Contact support".
4. **Permission denied** — icon + "You don't have access. Ask an admin for `manage_invoices`."

---

## 8. Key screens to design first (in order)

1. **Login + 2FA challenge** — email/password, "Remember me", forgot-password, 2FA code entry (6-digit, autoFocus, autocomplete `one-time-code`).
2. **Onboarding Wizard** — 5-step: pick template → setup COA → fiscal year → import opening balances → invite team. Progress bar top, skip available per step, `/v1/onboarding-wizard/*` APIs.
3. **Global Dashboard** — stat cards, revenue chart, AR aging, overdue list, quick actions.
4. **Invoices list → create → detail → payment flow → ETA submit**
   - List with advanced filters.
   - Create: client picker (async combobox hitting `/v1/clients?search=`), line-items grid with per-line VAT, totals calc live, terms, notes. Validation via `POST /v1/invoices/pre-check`.
   - Detail: timeline, PDF preview, Actions (Send, Post to GL, Credit Note, Cancel, Record Payment, **Submit to ETA**).
   - Payment: modal with amount, method, date, reference — optimistic add.
   - ETA submit: dedicated panel showing preparation errors (unmapped items, missing tax data) *before* submit, then live status polling.
5. **Journal Entries** — balanced debit/credit grid, per-line account picker, tab-to-next-line, running balance indicator, cannot save unless balanced. Post/Reverse actions.
6. **Bank Reconciliation** — **split screen** (the hardest UX):
   - Left: imported statement lines (unmatched).
   - Right: GL transactions (unmatched).
   - Middle column: drag-to-match or click-to-suggest. Smart-match button uses `POST /bank-reconciliations/{}/smart-match`. Confidence badges.
   - Bottom bar: reconciliation summary (opening balance, closing balance, variance — highlight red when ≠ 0).
   - Rules tab: create categorization rules from selected line ("Always categorize `Vodafone Egypt` as `Utilities`").
7. **Payroll Run** — select period → preview (employee list with gross, deductions, net) → calculate → review exceptions → approve → mark-paid. Per-employee payslip preview. Egyptian social insurance and tax deductions shown separately.
8. **Reports** — sidebar with report catalog, main area renders interactive report:
   - Filters above (date range, cost center, comparison period).
   - Table/chart view toggle.
   - Drill-down: click any number → account ledger for that GL.
   - Export: PDF (server-rendered via `/v1/reports/.../pdf`) + CSV + schedule.
9. **Custom Report builder** — 3-pane: data source picker (left), field-list drag to Columns/Filters/Group-by/Sort (middle), live preview table (right). Save to `/v1/custom-reports`.
10. **Client Portal** — distinct visual shell (brand gradient header, simpler nav, larger type):
    - Dashboard (outstanding / paid / overdue).
    - Invoice list with "Pay now" CTA → Paymob/Fawry gateway selector → redirect.
    - Documents list with upload dropzone.
    - Messages thread view.
    - Mobile-first.

---

## 9. Accessibility

- **WCAG 2.2 AA** minimum, target AAA for color contrast where possible.
- **Keyboard**: every interactive element reachable via Tab, operable via Enter/Space, dismissible with Esc. Visible focus ring (`:focus-visible`, 2px primary outline, 2px offset). No `outline: none` ever.
- **Skip links**: "Skip to content" on every page.
- **ARIA**: use semantic HTML first; `aria-label` only when text is truly missing.
- **Live regions**: `aria-live="polite"` for toasts; `"assertive"` for form submission errors.
- **Color independence**: never communicate only by color. Overdue badges have an icon + color. Negative numbers have `-` or `()` + color.
- **Icon-only buttons**: always `aria-label`.
- **Focus management**: return focus to trigger on modal close; trap focus inside modals; restore on route change.
- **Table semantics**: `<table>` with `<th scope>`, not `<div>` grids. If using TanStack Table with divs, add ARIA grid roles.
- **Touch targets**: 44×44px minimum on mobile.
- **Form errors**: associate via `aria-describedby`.

---

## 10. Performance

- **Target**: <1.5s TTI on Dashboard (4G, median mobile), <1s for subsequent in-app navigations.
- **Route-level code splitting** via App Router — automatic.
- **Prefetch on hover** for nav links (Next.js `<Link prefetch>` default).
- **TanStack Query**: persistent cache via `persistQueryClient` + `localStorage` for last-known data (stale-while-revalidate UX).
- **Optimistic updates** for: mark notification read, toggle active, simple status changes, payment add. Rollback on error.
- **Virtualization** for ledger, chart of accounts tree (when >200 nodes), journal line grids (when >50 lines), anomaly detection results.
- **Image optimization**: `next/image`, AVIF/WebP. Client logos cached aggressively.
- **Font loading**: `next/font` with `display: swap` and preload only body font + Arabic body font.
- **Bundle budget**: route chunks <150KB gzipped. Audit every addition.
- **No client-side charts over 10k points** — aggregate server-side.
- **Debounce** search inputs 250ms; throttle scroll-linked updates to `requestAnimationFrame`.

---

## 11. State management rules

- **Server state** → **TanStack Query** exclusively. Every API call goes through a typed hook (`useInvoices()`, `useInvoice(id)`, `useCreateInvoice()`). Never store server data in Zustand or React Context.
- **URL state** → search params (filters, pagination, tab) via `nuqs` or `useSearchParams`. Filters must be shareable via URL.
- **Form state** → `react-hook-form` only.
- **Local UI state** → `useState`. Escalate to Zustand only when the state crosses route boundaries (sidebar open, theme, locale, tenant).
- **NO REDUX. NO MOBX. NO RECOIL.**

---

## 12. Error handling

- **Typed API client** with a single `apiFetch<T>(path, opts)` wrapper:
  - Injects `Authorization: Bearer <token>` and `X-Tenant-Id` if needed.
  - Parses `{ message, errors }` Laravel shape into a typed `ApiError`.
  - Surfaces HTTP 401 → sign-out + redirect to `/login`.
  - Surfaces HTTP 403 → route-level permission boundary.
  - Surfaces HTTP 422 → map to react-hook-form field errors via `setError`.
  - Surfaces HTTP 429 → toast with "Too many requests, try again in Ns" and back off.
  - Surfaces HTTP 5xx → toast + Sentry capture + retry option.
- **Zod schema** for every response. Runtime validate in dev; log-only in prod for speed.
- **Error boundary** per route group (`app/(tenant)/error.tsx`). Include "Report issue" CTA.
- **Toast shape**: `sonner` — success (green, auto-dismiss 3s), error (red, persistent with close), info (neutral, 4s).

**Laravel error shape to handle**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "lines.0.amount": ["The amount must be positive."]
  }
}
```
Map dotted keys (`lines.0.amount`) to nested form paths automatically.

---

## 13. Auth flow

- **Sanctum bearer token**. Login `POST /v1/login` → `{ token, user, tenant }`.
- **Storage**: prefer **httpOnly cookie** set by a Next.js route handler (`app/api/auth/login/route.ts`) that proxies to Laravel, receives the token, sets it as `Secure, HttpOnly, SameSite=Lax` cookie. Client-side code never sees the token — the Next.js middleware adds the `Authorization` header when proxying. Fallback: `localStorage` with strict CSP.
- **Refresh**: Sanctum tokens don't auto-refresh; handle expiry by re-login. Show a non-blocking "Session expired" banner with re-auth dialog rather than hard redirect mid-form.
- **2FA enforcement**:
  - After login, call `GET /v1/2fa/status`. If required-but-not-verified → redirect to `/auth/2fa`. Call `POST /v1/2fa/verify` with 6-digit code.
  - Block all tenant routes until verified (the backend `enforce.2fa` middleware does this server-side — mirror it client-side for UX).
- **Tenant scoping**: every request carries an implicit tenant (from the token). If the user belongs to multiple tenants, show a tenant switcher; switching issues a new token.
- **Sign-out**: `POST /v1/logout` + clear cookie + TanStack Query cache clear + redirect to `/login`.
- **Route groups**: `(auth)` for public, `(tenant)` for authenticated tenant routes, `(portal)` for client portal, `(marketing)` for landing/blog.

---

## 14. API integration notes

- **Base URL**: `/v1`. Full API docs live at `/v1/docs` (Swagger UI) and OpenAPI spec at `/v1/docs/spec`. **Consider generating types from the spec** via `openapi-typescript` at build time.
- **Pagination**: Laravel default `{ data, links, meta: { current_page, per_page, total, last_page } }`. Your hooks accept `{ page, perPage, sort, ...filters }`.
- **List filtering**: search via `?search=`, faceted via `?status=`, `?client_id=`, `?date_from=`, `?date_to=`, multi-value via repeated keys or comma-separated (check per endpoint — inventory notes `/v1/reports/trial-balance` takes `accounts[]`).
- **Sorting**: `?sort=-date,amount` (leading `-` = desc).
- **Feature-flag checks**:
  - On login, persist `user.permissions[]` and `tenant.features[]` (derive from `/v1/subscription.plan.features`).
  - Hide nav items whose `feature` requirement is missing. On direct URL hit to a gated route → render a soft-paywall page: "This feature is in the Pro plan. [Upgrade]".
  - Permission-gate individual buttons (`<IfCan permission="post_journal_entries">`).
- **Idempotency**: `POST /v1/subscription/subscribe|renew` and similar are idempotent on the backend — send `Idempotency-Key` header (generate a UUID per user action).
- **Throttled endpoints**: reports, exports, 2FA verify, login. Handle 429 gracefully.
- **Webhooks (outbound)**: the app configures webhooks at `/v1/webhooks` — provide a settings UI listing available events (`GET /v1/webhooks/events`).
- **Payment gateways**: client portal payment triggers a redirect flow. After `POST /v1/portal/invoices/{id}/pay` with `{ gateway: 'paymob'|'fawry' }`, follow the `redirect_url` in the response.

### Example typed hook
```ts
// src/api/invoices.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { z } from 'zod';
import { apiFetch } from './client';

export const InvoiceSchema = z.object({
  id: z.number(),
  invoice_number: z.string(),
  client: z.object({ id: z.number(), name: z.string() }),
  date: z.string(),
  due_date: z.string(),
  total: z.number(),
  paid_amount: z.number(),
  currency: z.string().default('EGP'),
  status: z.enum(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
  lines: z.array(z.object({ /* ... */ })),
});
export type Invoice = z.infer<typeof InvoiceSchema>;

export function useInvoices(params: InvoiceListParams) {
  return useQuery({
    queryKey: ['invoices', params],
    queryFn: () => apiFetch('/v1/invoices', { params, schema: z.object({
      data: z.array(InvoiceSchema),
      meta: PaginationMetaSchema,
    })}),
  });
}

export function useCreateInvoice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (body: InvoiceCreateInput) =>
      apiFetch('/v1/invoices', { method: 'POST', body, schema: z.object({ data: InvoiceSchema })}),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['invoices'] }),
  });
}
```

---

## 15. Definition of Done per module

Every module ships only when all the following are true. **No exceptions**.

- [ ] **List page** — TanStack Table, server pagination, filters, sort, bulk actions, export, empty/loading/error states.
- [ ] **Detail page** — header, tabs, activity timeline, related entities, primary/secondary actions.
- [ ] **Create form** — validation (Zod + RHF), optimistic where safe, keyboard shortcuts, draft autosave for complex forms.
- [ ] **Edit form** — same as create, pre-filled.
- [ ] **Delete / destructive actions** — AlertDialog, typed confirmation for irreversible, undo toast where possible.
- [ ] **Loading skeletons** — shape-matching, not generic spinners.
- [ ] **Empty state** — illustration + explanation + primary CTA.
- [ ] **Error state** — icon + message + retry.
- [ ] **Permission boundary** — hides nav, disables buttons, shows friendly message on direct URL hit.
- [ ] **Plan/feature gate** — hides module when plan lacks feature; soft-paywall on URL hit.
- [ ] **Mobile viewport** — works at 360px; no horizontal scroll; primary actions reachable.
- [ ] **RTL layout** — tested with `dir="rtl"`; icons mirror; logical properties only.
- [ ] **Arabic translations** — complete for the module; no key names showing; plural forms correct.
- [ ] **Keyboard navigation** — full Tab order, focus-visible, no mouse-only paths.
- [ ] **aria labels** — icon buttons labeled; table columns `<th scope>`; modals labeled.
- [ ] **Performance** — list under 150KB route chunk; no dropped frames on scroll.
- [ ] **Tests** — at least one Playwright happy-path and one Vitest unit test per critical form.

---

## 16. What to **NOT** build

- **No Super Admin panel.** Filament handles tenant/platform management at `/admin`. Do not replicate.
- **No 2FA setup for super admins.** Filament owns this.
- **No server-side rendering of sensitive pages beyond the shell.** All tenant data fetched client-side through TanStack Query. SSR only: marketing landing, blog, public plan pages.
- **No hand-rolled design system.** Use shadcn primitives. If a primitive is missing, add via `shadcn@latest add <component>`.
- **No inline SQL, no direct DB access, no assumptions about database schema.** The Laravel API is the only contract.
- **No blocking full-page spinners.** Skeleton-first.
- **No modal chains deeper than one level.** Replace with a full-page form or sheet.
- **No PDF generation in the browser for financial reports.** Use the backend `/v1/reports/.../pdf` endpoints or async via `POST /v1/reports/pdf/async`.
- **No client-side tax or payroll math of record.** Preview is fine; source of truth is the backend calculator.
- **No GraphQL.** Stay on REST + Zod.
- **No offline-first.** Muhasebi requires connectivity. Be graceful on network loss (banner + retry), don't pretend to be a local app.
- **No custom fonts beyond Inter + Cairo/IBM Plex Arabic + JetBrains Mono.**
- **No emoji in the UI.** Lucide icons only.
- **No "AI chat" as a UI pattern** unless explicitly specified. Existing AI features (account suggestions, ETA code suggestions, bank categorization learning) surface inline as ghost suggestions, never as a chat modal.

---

## 17. Directory layout (reference)

```
src/
  app/
    (marketing)/        # landing, blog, pricing
    (auth)/             # login, register, 2fa, forgot
    (tenant)/           # admin panel shell
      dashboard/
      invoices/
      bills/
      journal-entries/
      bank-reconciliations/
      payroll/
      reports/
      ...
    (portal)/           # client portal shell
      dashboard/
      invoices/
      documents/
      messages/
  api/                  # API client, hooks, schemas (mirrors backend modules)
  components/
    ui/                 # shadcn primitives
    tables/             # shared TanStack Table wrappers
    forms/              # shared form controls (MoneyInput, AccountPicker, ClientPicker, etc.)
    charts/             # Recharts wrappers (theme-aware)
    layout/             # Sidebar, Topbar, CommandPalette, TenantSwitcher
  lib/
    i18n/
    money.ts            # Intl.NumberFormat wrappers
    dates.ts            # date-fns wrappers
    permissions.ts      # <IfCan>, hooks
    features.ts         # <IfPlanHas>, hooks
    auth.ts
  hooks/
  messages/
    ar.json
    en.json
  styles/
    globals.css
```

---

## 18. First-week milestone

Build in this order. Do **not** scaffold beyond what's listed — ship, then iterate.

**Day 1** — Project scaffold, Tailwind v4, shadcn init, `next-intl` with ar/en, RTL toggle, theme toggle, font loading, base layout (sidebar + topbar), empty Command Palette.

**Day 2** — API client (`apiFetch`), Zod runtime check, error toast pipeline, login page + 2FA, cookie-based auth, auth middleware, tenant context.

**Day 3** — Global Dashboard with live data from `/v1/dashboard`. 4 stat cards + 1 chart. Quick actions bar.

**Day 4** — Clients module (list + detail + create + edit + delete) — end-to-end as the reference implementation.

**Day 5** — Invoices list + detail + create (line items grid, totals, VAT). Payment modal.

**Day 6** — Journal Entries (balanced grid) + Chart of Accounts tree.

**Day 7** — Reports shell (Trial Balance + P&L + Balance Sheet) with filters, PDF export. Onboarding wizard.

After week 1, the pattern library is proven; the remaining 22 modules are variations of list/detail/form on the same primitives.

---

## 19. Quality bar (non-negotiable)

- Every screen exists in **light + dark + RTL + LTR** — four variants, visually regressed.
- Every money display uses the shared `<Money>` component.
- Every date uses the shared `<Date>` component.
- Every list uses the shared `<ResourceTable>` wrapper.
- Every form uses `react-hook-form` + Zod. No uncontrolled mutations.
- Every async boundary has skeleton + error + empty.
- Every destructive action has confirmation.
- Every module has permission + feature gates.
- Every page works on 360px width.
- Every keyboard-only user can reach every action.

---

## 20. Egyptian market specifics (do not forget)

- **EGP** default, thousands separator per locale.
- **Arabic numerals toggle** — some users prefer ٠-٩.
- **Tax ID** field on Clients/Vendors: 9-digit Egyptian tax ID with checksum validation (backend validates; frontend can soft-check).
- **Phone numbers**: Egyptian format `+20 1X XXXX XXXX`. Default country code `+20`.
- **National ID** (on employees): 14 digits, validate mask.
- **ETA fields**: every product/account mappable to an ETA item code (GPC or EGS). Surface "Not mapped — will block ETA submission" warnings inline.
- **Hijri toggle** on invoice/report rendering, user preference.
- **Calendar week** starts Saturday in Arabic locale, Monday in English. `date-fns` respects locale.
- **Weekends**: Friday + Saturday default for Egyptian tenants — respect in attendance screens.
- **Right-to-left invoice PDFs** — the backend handles this; your preview iframe just renders.

---

## 21. Ship it

Read `docs/features/FEATURES.md` for the full feature list and `docs/frontend/IA.md` for the complete sidebar tree before starting. When in doubt about an API field, hit `/v1/docs` in the running app. Keep PRs small, ship the Clients module first as the reference, and copy that pattern for the remaining 22 modules.

Questions to raise before writing code:
1. Deployment target — Vercel, self-host on Egyptian IaaS, or containerized?
2. Domain strategy — subdomains per tenant (`acme.muhasebi.app`) or path-based (`/t/acme`)? Affects cookie + middleware.
3. Analytics — PostHog, Plausible, or none?
4. Error tracking — Sentry?
5. Any must-have module from IA.md to deprioritize for MVP?

Otherwise: scaffold, build, ship.
