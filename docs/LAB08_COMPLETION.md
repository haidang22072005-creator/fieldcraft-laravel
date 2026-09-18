# FIELDCRAFT Lab 8 completion

## Scope

Lab 8 completes the existing admin architecture without replacing the established authorization, order-state, payment, GHN, Customer 360, Support Center, or Lab 7 livechat flows.

| Area | Existing reused | Added or completed | Verification |
| --- | --- | --- | --- |
| Orders | `OrderPolicy`, `OrderStatus`, `CancelOrder`, GHN services, order detail | Search by order/customer/phone/GHN/product, Vietnamese filters, status tabs, sort, bounded 25/50/100 pagination, eager-loaded list data | `tests/Feature/Lab8Test.php`, existing order/cancellation tests |
| Reports | Existing dashboard intelligence remains unchanged | HTML report page, bounded real aggregations, valid order-based revenue, category/day/month/year/payment breakdowns, chart/table toggle | `tests/Feature/Lab8Test.php` |
| Users | `AccountController`, role middleware, password broker, Customer 360 | Phone/avatar/verification metadata, Customer 360 links, safe staff creation and role updates retained; no unsafe delete added | `tests/Feature/Lab8Test.php`, existing admin account tests |

## Routes

- `admin.orders.index`, `admin.orders.show`, `admin.orders.status` and existing GHN/refund routes remain unchanged.
- `admin.reports.index` renders the report UI.
- `admin.reports.charts` exposes the same authenticated report aggregates for the report surface.
- `admin.accounts.index`, `admin.accounts.store`, and `admin.accounts.role` remain the account-management routes.

## Revenue rules

Report revenue is counted once per order. Only completed COD orders or completed online orders whose payment status is `paid` are valid. Cancelled, failed/unpaid online, and orders with a confirmed refunded payment are excluded. Multiple payment attempts do not multiply an order.

## Deliberate non-additions

No destructive account delete was introduced because the current account architecture has safe role changes and password reset through the existing broker. Existing admin and customer authorization boundaries remain in place.
