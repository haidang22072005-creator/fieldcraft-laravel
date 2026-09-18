# Lab 8 Admin Orders, Reports, and Users Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the missing Lab 8 admin order operations, safe real-data reporting, and user-management presentation without duplicating existing FIELDCRAFT architecture.

**Architecture:** Extend the existing `OrderController`, `OrderStatus`, `CancelOrder`, `AdminIntelligenceService`, and `AccountController` boundaries. Add a focused `AdminReportService` for bounded SQL aggregations and an HTML `ReportController`/view; keep JSON chart data fetch-only and preserve existing dashboard/intelligence routes.

**Tech Stack:** Laravel, Eloquent query builders, Blade, PHPUnit feature tests, existing FIELDCRAFT Admin Pro Max styles.

**Spec:** Teacher Lab 8 document supplied in the user attachment and pasted Lab 8 requirements.

## Global Constraints

- Preserve existing authentication, roles, OrderStatus, GHN, payment/refund, Customer360, Support Center, and Lab 7 Livechat behavior.
- Never bypass `OrderStatus`, `OrderPolicy`, `CancelOrder`, GHN cancellation, stock restoration, or coupon restoration.
- Reports count only valid completed revenue and never double-count payment attempts or confirmed refunds.
- Admin lists use bounded pagination; no unbounded admin `get()`.
- Mutations retain CSRF protection; user content is escaped/text-only; no secrets or `.env` changes.
- Keep Vietnamese labels and current Admin Pro Max UI; do not expose JSON endpoints through sidebar navigation.

### Task 1: Lock down admin order list behavior

**Files:**
- Modify: `app/Http/Controllers/Admin/OrderController.php`
- Modify: `resources/views/admin/orders/index.blade.php`
- Test: `tests/Feature/Lab8Test.php`

- [ ] Write failing feature assertions for GHN/product search, shipping filter, safe sort options, page sizes 25/50/100, status tabs, and customer authorization.
- [ ] Run the focused test and confirm it fails because the current controller only supports fixed 20-page pagination and lacks GHN/product/sort/shipping handling.
- [ ] Add validated filters `shipping_status`, `sort`, and bounded `per_page`; search `ghn_order_code` and `items.product_name`; map sort values to a fixed column/direction list; paginate with the selected 25/50/100 size.
- [ ] Add Vietnamese filter controls and status-tab links using `UiLabels`, preserving query strings and never printing raw status values.
- [ ] Run the focused order tests and confirm green.

### Task 2: Preserve order transitions and detail safety

**Files:**
- Inspect only unless tests expose a regression: `app/Actions/CancelOrder.php`, `app/Http/Controllers/Admin/OrderController.php`, `resources/views/admin/orders/show.blade.php`
- Test: `tests/Feature/Lab8Test.php`

- [ ] Add tests for valid/invalid `OrderStatus` transitions, delivering/waybill cancellation failure, paid online cancellation protection, and idempotent stock/coupon restoration.
- [ ] Run tests red where coverage is missing.
- [ ] Keep all status mutation paths through existing transition and cancellation services; do not assign status directly in the new work.
- [ ] Add only missing Vietnamese detail labels/fields needed for customer contact, address, totals, payment/refund, GHN, and item variants.
- [ ] Run order safety tests and existing payment/GHN tests green.

### Task 3: Add bounded report aggregation service and routes

**Files:**
- Create: `app/Services/AdminReportService.php`
- Create: `app/Http/Controllers/Admin/ReportController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Lab8Test.php`

- [ ] Write failing tests for admin-only report routes, valid revenue exclusion, duplicate payment-attempt deduplication, COD/MoMo/bank QR totals, category/day/month/year/payment aggregations, and customer/order KPI values.
- [ ] Run the focused report tests and confirm the missing report route/service fails.
- [ ] Implement a reusable valid-order query: completed orders; COD accepted by completed business state; online methods require `payment_status=paid`; exclude orders having a confirmed `payments.refund_status=refunded`; group by order, never payment attempts.
- [ ] Implement bounded SQL aggregations for summary, category, daily, monthly, yearly, and payment method data with date filters and database-driver-safe period expressions.
- [ ] Add `GET admin.reports.index` for HTML and `GET admin.reports.charts` for fetch-only JSON; keep the sidebar linked only to HTML.
- [ ] Run report tests green and verify no report query loads all orders into PHP.

### Task 4: Build the report UI and admin navigation

**Files:**
- Create: `resources/views/admin/reports/index.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Lab8Test.php`, `tests/Feature/AdminPanelTest.php`

- [ ] Add failing assertions for Vietnamese report navigation, summary KPIs, data-table headings, chart/table tabs, and no JSON sidebar link.
- [ ] Implement responsive FIELDCRAFT-styled report tabs with real server-provided tables and CSS/SVG bar charts derived from the same report arrays; include truthful empty-state text.
- [ ] Add `BÁO CÁO & PHÂN TÍCH` sidebar navigation without removing Orders, Chat, Support, Customer360, Finance, Loyalty, or Kanban links.
- [ ] Run UI route assertions and inspect rendered HTML for escaped data and absence of raw JSON navigation.

### Task 5: Complete existing account/user presentation safely

**Files:**
- Modify: `app/Http/Controllers/Admin/AccountController.php`
- Modify: `resources/views/admin/accounts/index.blade.php`
- Test: `tests/Feature/Lab8Test.php`, `tests/Feature/AdminPanelTest.php`

- [ ] Write failing assertions for phone search and display of avatar/initials, phone, verification/account status, customer 360 links, hashed creation, duplicate email, and privilege boundaries.
- [ ] Extend existing bounded account search with phone; retain existing super-admin-only staff creation and role update guards.
- [ ] Render avatar/initials, phone, role, verification status, joined date, and customer 360 links using escaped Blade output; do not add unsafe delete CRUD because current password-reset/account architecture protects business history.
- [ ] Run account tests green.

### Task 6: Add Lab 8 completion documentation

**Files:**
- Create: `docs/LAB08_COMPLETION.md`

- [ ] Document every Orders, Reports, and Users requirement in the requested table with `DONE EXISTING`, `DONE ADDED`, or `NOT APPLICABLE`, exact routes/files, and test references.
- [ ] Include the FIELDCRAFT upgrades: GHN workflow, MoMo/payOS/bank QR, refund protection, roles, broker password reset, real analytics, and authorization.

### Task 7: Full verification and commit

**Files:**
- No further production edits after verification begins.

- [ ] Run `php artisan migrate` without destructive refresh/wipe, using the workspace-safe test/in-memory environment if the project has no `.env`.
- [ ] Run `php artisan test`, `php artisan route:list`, `git diff --check`, and `git status --short`.
- [ ] Perform a read-only self-audit for storage, security, performance, UI, and regression requirements.
- [ ] Commit with `git add .` and `git commit -m "Complete Lab 8 admin orders reports and users"`.
