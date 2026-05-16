# Kaabosh — Known gaps & future-feature decisions

Captured 2026-05-16 during the test-suite triage. The mechanical issues
(route wiring lost in the muhasebi→kaabosh fork, model-binding param
mismatches, strict-mode `firm_id`/`firm_role` access, stale enum
expectations, orphaned muhasebi test files) were fixed — suite went from
**96 → 19 failing**.

The remaining 19 needed product / architecture decisions, grouped
below. **All six were resolved across PRs #3–#8 (2026-05-16); the
suite is now fully green (453 passed, 0 failing).** Each section
records the decision taken.

## 1. Auth is still muhasebi Model-B (4 — `AuthTest`)

`AuthService::register()` creates a `Firm` + sets `firm_id`; `login()`
rejects any non-superadmin, non-client user with no `firm_id`
("This account is not attached to an accounting firm").

Kaabosh is single-company-per-account — there is no firm. **Decision
needed:** strip the Firm/Model-B machinery from registration + login so
a registration creates one Company and that user logs straight in. This
touches `AuthService`, `Firm` domain wiring, and the `users.firm_id`
column. Sizeable, deliberate — its own PR.

## 2. Filament admin panel resources — ✅ DONE (PR #8, 2026-05-16)

`CouponResource`/dashboard/queue-monitor/subscription-payments pages
404'd. Two root causes, not "carryover to drop":

1. **Panel path.** `AdminPanelProvider` was deliberately mounted at
   `->path('/')` (the Laravel domain is admin-only; SPA/marketing live
   on the Nuxt frontend), but the tests still asserted the old
   `/admin/*` URLs. **Decision: keep `/`** (honour the documented
   rationale); the 4 test files were rewritten to the real root paths.
2. **Missing resource.** `CouponResource` was never created in the
   fork (the `Coupon` model/enum/`discountFor` + subscription
   row-actions all existed and passed). Built `CouponResource` +
   List/Create/Edit pages (Billing nav group, super-admin gated via
   the existing panel `canAccessPanel`). Coupons are **kept** as a
   real kaabosh billing feature, per the "keep & repair all" call.

## 3. Subscription / plan-gating — ✅ DONE (PR #4, 2026-05-16)

Plan-feature gating + usage-threshold warnings assumed the muhasebi
plan/subscription seed + firm-scoped subscriptions.

Resolved: `UsageService::getUsage()` rebuilt against kaabosh metered
resources (active users / clients / documents / storage bytes / API
calls) vs. the active plan's `limits` map — the muhasebi accounting
metrics were dropped. `UsageWarningService` trimmed to the kaabosh
metric set. `PlanGatingTest` realigned off the deleted muhasebi
`e_invoice`/ETA route onto the real kaabosh `feature:payroll` gate
(`/api/v1/payroll`; `starter` lacks `payroll`, `professional` has it).

## 4. Admin revenue reporting — ✅ DONE (PR #6, 2026-05-16)

The original triage assumed revenue analytics were accounting-derived
and needed a new MRR source. They weren't: `AdminDashboardService`
already computes MRR/ARR/`getMonthlyRevenue`/`getRevenueByPlan` from
`SubscriptionPayment` — the correct kaabosh subscription-MRR source —
and `AdminDashboardController::monthlyRevenue`/`revenueByPlan` already
exist. It was fork-lost route wiring after all: `admin/revenue/monthly`
and `admin/revenue/by-plan` were simply never registered. Routes added.

## 5. Tenant-suspension semantics — ✅ DONE (PR #4, 2026-05-16)

`IdentifyTenant` is already correct single-company (404 when the user's
company is unresolvable, 403 when suspended). The `MiddlewareTest` 404
case still asserted the muhasebi X-Tenant-header behaviour; rewritten to
express single-company semantics (user with no resolvable company → 404).

## 6. RBAC role-presets authorization — ✅ DONE (PR #5, 2026-05-16)

`GET /rbac/role-presets` was gated by `permission:manage_roles`, which
**no role in `config/permissions.php` actually has** — so only
SuperAdmin could ever reach it.

Decision: the endpoint is read-only metadata feeding the team-
management "Apply preset" UI, so it belongs to `manage_team` (every
team admin has it). Split `rbac/role-presets` into its own
`permission:manage_team` group; the mutating RBAC-admin endpoints
(`rbac/roles`, `rbac/permissions`, `rbac/users/{user}/roles`) stay
behind the stricter `manage_roles` gate.

---

### Recommended order
1. **#1 Auth single-company** — unblocks real onboarding/login; highest user impact.
2. **#3/#4 Subscription/plan model** — defines kaabosh billing.
3. **#2 Filament panel triage** — admin UX.
4. **#5/#6** — small, fold into the relevant PR above.

Each is a separate scoped PR.
