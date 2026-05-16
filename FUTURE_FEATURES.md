# Kaabosh — Known gaps & future-feature decisions

Captured 2026-05-16 during the test-suite triage. The mechanical issues
(route wiring lost in the muhasebi→kaabosh fork, model-binding param
mismatches, strict-mode `firm_id`/`firm_role` access, stale enum
expectations, orphaned muhasebi test files) were fixed — suite went from
**96 → 19 failing**.

The remaining **19 failures are not bugs to patch**; they need product /
architecture decisions. Grouped below.

## 1. Auth is still muhasebi Model-B (4 — `AuthTest`)

`AuthService::register()` creates a `Firm` + sets `firm_id`; `login()`
rejects any non-superadmin, non-client user with no `firm_id`
("This account is not attached to an accounting firm").

Kaabosh is single-company-per-account — there is no firm. **Decision
needed:** strip the Firm/Model-B machinery from registration + login so
a registration creates one Company and that user logs straight in. This
touches `AuthService`, `Firm` domain wiring, and the `users.firm_id`
column. Sizeable, deliberate — its own PR.

## 2. Filament admin panel resources (7 — `tests/Feature/Admin/*`)

`CouponResource`, admin dashboard Filament pages, etc. fail to load.
These are Filament-panel carryover (coupons/trial-extension are muhasebi
billing concepts). **Decision:** which admin-panel resources does
kaabosh keep? Triage the Filament panel separately from the API.

## 3. Subscription / plan-gating (4 — `PlanGating`, `UsageWarningServiceTest`)

Plan-feature gating + usage-threshold warnings assume the muhasebi
plan/subscription seed + firm-scoped subscriptions. Needs the kaabosh
plan catalogue + subscription model defined before these pass.

## 4. Admin revenue reporting (2 — `AdminDashboardTest`)

`/api/v1/admin/revenue/monthly` & `/by-plan` 404 — revenue analytics
were accounting-derived. A kaabosh revenue source (subscription MRR)
must be defined; not a route-wiring fix.

## 5. Tenant-suspension semantics (1 — `MiddlewareTest`)

`IdentifyTenant` no longer 404s a suspended tenant. In single-company
kaabosh, "tenant suspension" semantics differ from muhasebi — confirm
the intended behaviour, then enforce in the middleware.

## 6. RBAC role-presets authorization (1 — `RbacPresetsTest`)

`GET /rbac/role-presets` now routes but returns 403 — the test's actor
isn't granted `manage_roles`. Decide whether presets are readable to a
broader role or fix the test's permission setup.

---

### Recommended order
1. **#1 Auth single-company** — unblocks real onboarding/login; highest user impact.
2. **#3/#4 Subscription/plan model** — defines kaabosh billing.
3. **#2 Filament panel triage** — admin UX.
4. **#5/#6** — small, fold into the relevant PR above.

Each is a separate scoped PR.
