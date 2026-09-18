# Lab 7 Livechat Design

## Goal

Add a bounded, authenticated customer-to-admin direct messaging feature while preserving the existing ticket-based Support Center.

## Existing architecture audit

- The repository has `SupportTicket` and `SupportMessage` for ticket conversations.
- There is no direct-chat `Message` model, `messages` table, chat controller, or chat route.
- Existing Support Center routes remain under `/support/tickets` and `/admin/support/tickets` and must not be repurposed.
- Customer pages are standalone Blade documents rather than a shared customer layout. The storefront and settings pages are the customer surfaces suitable for the floating chat entry point; the admin dashboard already uses `resources/views/layouts/admin.blade.php`.

## Architecture

Create an additive `messages` table and `Message` model with `sender_id`, `receiver_id`, `content`, `is_read`, timestamps, foreign keys to users, and indexes for both conversation directions and chronological lookup. `content` is limited to 5,000 characters and is returned as plain text only.

Use a dedicated `ChatMessageService` for recipient selection, conversation queries, bounded history, and read-state updates. Customers never submit a receiver; the service selects a database user with role `admin` or `super-admin`, preferring `admin` deterministically and returning a truthful unavailable response if none exists. Admin operations accept only route-bound users whose role is `customer` and are protected by the existing admin middleware.

Every history loader queries the newest 100 messages using descending `created_at` and `id`, then reorders that bounded collection ascending before serialization. This preserves recent-message bounds while making the first rendered message the oldest retained message and the last message the newest.

## Routes and flows

Customer routes use a dedicated `/chat/messages` prefix under `auth` and `verified` middleware:

- `GET /chat/messages` loads the customer conversation and marks unread admin-to-customer messages read.
- `POST /chat/messages` validates only `content`, selects the server-side admin recipient, persists the message, and returns the bounded chronological conversation.

Admin routes use `/admin/chat` inside the existing `auth` and `role:super-admin,admin` group:

- `GET /admin/chat/customers` returns customers with at least one direct message, latest-message preview/time, and unread customer-to-admin count without per-customer N+1 queries.
- `GET /admin/chat/customers/{user}/messages` validates the target is a customer, marks unread customer-to-admin messages read, and returns its bounded chronological conversation.
- `POST /admin/chat/customers/{user}/messages` validates the customer target and content, sends from the authenticated admin, and returns the bounded chronological conversation.

Existing Support Center route names and controllers remain unchanged.

## UI behavior

Add a `resources/views/components/livechat.blade.php` partial and include it on the customer storefront/settings surfaces. It provides the `💬 HỖ TRỢ TRỰC TUYẾN` floating button, popup title `FIELDCRAFT SUPPORT`, conversation area, composer, loading/error states, safe DOM rendering with `textContent` and `createElement`, and responsive sizing that does not cover important mobile controls.

Add a separate `CHAT NHANH` section to the admin layout/dashboard. The left panel lists only customers with conversations, with initials/avatar, name, latest message/time, and unread count. The right panel renders customer/admin bubbles and a safe reply form. Support Center remains a separate panel and route family.

Polling runs approximately every three seconds only while the relevant customer popup or admin conversation is open. A single in-flight request is allowed; timers are cleared when closed or inactive. Every fetch checks `response.ok`, parses error bodies safely, shows Vietnamese error text, includes CSRF on POST, disables the send button during submission, and restores controls on failure.

## Read/unread semantics

Read updates are scoped to the current authenticated customer or selected customer/admin direction and use one idempotent update query. Customer loads mark admin-to-customer messages read. Admin opens mark customer-to-admin messages read. Admin list unread counts use the inverse direction and remain database-backed.

## Testing and documentation

Add feature tests covering persistence, customer/admin send and receive, ownership and role authorization, server-side recipient selection, empty/oversized content validation, read/unread idempotency, newest-100 chronological history, plain-text XSS handling, and Support Center regression including ordering. Run the existing full suite unchanged.

Create `docs/LAB07_COMPLETION.md` with a requirement/status/implementation/route-file/test traceability table covering storage, both flows, UI, polling, read/unread, and security.

## Constraints

- Do not replace or reuse `support_tickets`/`support_messages` for direct chat.
- Do not use unbounded history `get()` or `oldest()->limit(100)`.
- Do not use raw `innerHTML` for message, sender, or customer values.
- Do not use a hard-coded admin user ID.
- Do not change existing route names, `.env`, secrets, or unrelated CRM behavior.
- Do not run `migrate:fresh`, `db:wipe`, reset, or push.
