# Lab 7 Livechat Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a bounded, secure customer-to-admin Livechat that coexists with the existing ticket Support Center.

**Architecture:** Add a separate `messages` persistence model and a shared `ChatMessageService`. Customer and admin controllers expose JSON endpoints under distinct route families; each endpoint retrieves the newest 100 messages descending and reorders the bounded collection chronologically before returning it. Customer UI is a reusable Blade component included on standalone customer surfaces, while admin UI is a separate `/admin/chat` page reached from the existing admin layout.

**Tech Stack:** Laravel 12, PHP, Eloquent, Blade, vanilla JavaScript, PHPUnit/Pest feature tests, SQLite test database.

**Spec:** `docs/superpowers/specs/2026-09-18-lab07-livechat-design.md`

## Global Constraints

- Do not replace or reuse `support_tickets`/`support_messages` for direct chat.
- Do not use unbounded history `get()` or `oldest()->limit(100)`.
- Do not use raw `innerHTML` for message, sender, or customer values.
- Do not use a hard-coded admin user ID.
- Do not change existing route names, `.env`, secrets, or unrelated CRM behavior.
- Do not run `migrate:fresh`, `db:wipe`, reset, or push.
- Keep Support Center routes, authorization, ordering behavior, and tests intact.

---

### Task 1: Add direct-message persistence and model relations

**Files:**
- Create: `database/migrations/2026_09_18_000100_create_messages_table.php`
- Create: `app/Models/Message.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/LivechatTest.php`

**Interfaces:**
- Produces `Message::sender()`, `Message::receiver()`, `User::sentMessages()`, and `User::receivedMessages()`.
- The migration produces `messages.sender_id`, `messages.receiver_id`, `messages.content`, `messages.is_read`, timestamps, foreign keys, and conversation/time indexes.

- [ ] **Step 1: Write the failing persistence test**

Create a feature test that creates a customer, admin, and `Message`, then asserts the row stores the two user IDs, content, false read state, and both user relations.

```php
public function test_direct_messages_persist_with_sender_receiver_and_read_state(): void
{
    $customer = User::factory()->create(['role' => 'customer']);
    $admin = User::factory()->create(['role' => 'admin']);

    $message = Message::create([
        'sender_id' => $customer->id,
        'receiver_id' => $admin->id,
        'content' => 'Xin chào FIELDCRAFT',
        'is_read' => false,
    ]);

    $this->assertDatabaseHas('messages', ['id' => $message->id, 'sender_id' => $customer->id, 'receiver_id' => $admin->id, 'is_read' => false]);
    $this->assertSame($customer->id, $message->fresh()->sender->id);
    $this->assertSame($admin->id, $message->fresh()->receiver->id);
}
```

- [ ] **Step 2: Run the test and verify it fails because the direct-message schema/model does not exist**

Run: `php artisan test tests/Feature/LivechatTest.php --filter=direct_messages_persist`

Expected: FAIL with the missing `Message` class or `messages` table, not a test syntax error.

- [ ] **Step 3: Add the migration, model, and user relations**

Use a `messages` table with `foreignId('sender_id')` and `foreignId('receiver_id')` constrained to `users`, `text('content')`, boolean `is_read` defaulting false, timestamps, indexes `['sender_id','receiver_id','created_at']`, `['receiver_id','sender_id','created_at']`, and an index on `is_read`. In `Message`, fill only sender/receiver/content/is_read and cast `is_read` to boolean. Add the two inverse `HasMany` relations to `User`.

- [ ] **Step 4: Run the focused test and verify it passes**

Run: `php artisan migrate --force; php artisan test tests/Feature/LivechatTest.php --filter=direct_messages_persist`

Expected: PASS.

### Task 2: Implement bounded chat service and customer/admin APIs

**Files:**
- Create: `app/Services/ChatMessageService.php`
- Create: `app/Http/Controllers/ChatMessageController.php`
- Create: `app/Http/Controllers/Admin/ChatMessageController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/LivechatTest.php`

**Interfaces:**
- `ChatMessageService::customerConversation(User $customer): Collection` marks admin-to-customer unread messages read, loads at most 100 messages, and returns them oldest-to-newest.
- `ChatMessageService::adminCustomers(): LengthAwarePaginator` returns only customers with direct conversations, latest preview/time, and unread customer-to-admin count.
- `ChatMessageService::adminConversation(User $customer): Collection` marks customer-to-admin unread messages read and returns the bounded chronological conversation.
- Customer routes: `GET/POST /chat/messages`, named `chat.messages.index` and `chat.messages.store`.
- Admin routes: `GET /admin/chat` named `admin.chat`, `GET /admin/chat/customers` named `admin.chat.customers.index`, `GET/POST /admin/chat/customers/{user}/messages` named `admin.chat.customers.messages` and `admin.chat.customers.messages.store`.

- [ ] **Step 1: Add failing API/security/bounds tests**

Cover customer send/receive, server-side admin recipient selection, customer ownership, admin-only routes, invalid customer target, empty/overlong content, read updates, and 105-message newest-100 chronological behavior. Assert the returned messages are `Message 6` through `Message 105` in that order.

```php
public function test_chat_history_returns_newest_hundred_in_chronological_order_and_marks_reads(): void
{
    $customer = User::factory()->create(['role' => 'customer']);
    $admin = User::factory()->create(['role' => 'admin']);
    $base = now()->startOfSecond();

    foreach (range(1, 105) as $number) {
        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $customer->id,
            'content' => 'Message '.$number,
            'is_read' => false,
            'created_at' => $base->copy()->addSeconds($number),
            'updated_at' => $base->copy()->addSeconds($number),
        ]);
    }

    $messages = $this->actingAs($customer)->getJson(route('chat.messages.index'))->assertOk()->json('data');

    $this->assertCount(100, $messages);
    $this->assertSame('Message 6', $messages[0]['content']);
    $this->assertSame('Message 105', $messages[99]['content']);
    $this->assertSame(range(6, 105), array_map(fn (array $message): int => (int) str_replace('Message ', '', $message['content']), $messages));
    $this->assertSame(0, Message::where('receiver_id', $customer->id)->where('is_read', false)->count());
}
```

- [ ] **Step 2: Run the focused tests and verify they fail for missing routes/service**

Run: `php artisan test tests/Feature/LivechatTest.php --filter='chat|conversation|message'`

Expected: FAIL because the new route/controller/service is not implemented.

- [ ] **Step 3: Implement service and controllers minimally**

Select a database recipient using `User::whereIn('role', ['admin', 'super-admin'])->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")->orderBy('id')->first()`. Ignore any customer-supplied receiver. Use explicit descending `created_at` and `id`, `limit(100)`, then `sortBy` ascending on timestamp and ID. Use `with(['sender:id,name,avatar','receiver:id,name,avatar'])` to avoid N+1. Admin customer list must use correlated subqueries/withCount rather than loading all messages per customer. Return 422 validation responses and a 503 Vietnamese error when no admin recipient exists; do not expose exceptions.

- [ ] **Step 4: Add routes with existing Support Center routes untouched**

Place customer chat routes in a new authenticated/verified `chat` group and admin chat routes inside the existing admin role group. Bind `{user}` and abort with 404 unless the target role is `customer`.

- [ ] **Step 5: Run the focused tests and verify they pass**

Run: `php artisan test tests/Feature/LivechatTest.php --filter='chat|conversation|message'`

Expected: PASS, including persistence, send/receive, ownership, role checks, validation, read/unread, and chronological bounded history.

### Task 3: Add the safe customer popup and polling

**Files:**
- Create: `resources/views/components/livechat.blade.php`
- Modify: `resources/views/storefront.blade.php`
- Modify: `resources/views/settings.blade.php`
- Test: `tests/Feature/LivechatTest.php`

**Interfaces:**
- The component consumes `route('chat.messages.index')` and `route('chat.messages.store')` and renders only authenticated customers.
- `renderLivechatMessages` creates DOM nodes and assigns message content/sender/timestamp through `textContent`; no user-controlled value is interpolated into HTML.

- [ ] **Step 1: Add failing view/XSS/polling contract tests**

Assert authenticated customer storefront/settings responses contain `HỖ TRỢ TRỰC TUYẾN`, `FIELDCRAFT SUPPORT`, chat route URLs, and safe DOM markers such as `textContent`/`createElement`; assert a stored `<img ...>` message is not emitted as a raw executable markup fragment by the server-rendered view.

- [ ] **Step 2: Run the view tests and verify they fail because the component is absent**

Run: `php artisan test tests/Feature/LivechatTest.php --filter='popup|XSS|view'`

Expected: FAIL with missing UI markers.

- [ ] **Step 3: Implement the component and include it on both customer surfaces**

Use static Blade structure and CSS with a fixed floating button, a bounded mobile-friendly popup, loading/error nodes, a message container, textarea, and disabled send button. JavaScript must check `response.ok` on every fetch, parse JSON failure bodies defensively, show Vietnamese errors, include the CSRF token for POST, reject duplicate sends while a request is active, and restore controls in `finally`. Poll every 3,000 ms only while open and visible, clear the interval on close/hidden state, and never use raw `innerHTML` for message/sender values.

- [ ] **Step 4: Run the view/XSS tests and verify they pass**

Run: `php artisan test tests/Feature/LivechatTest.php --filter='popup|XSS|view'`

Expected: PASS.

### Task 4: Add admin Chat nhanh page and navigation

**Files:**
- Create: `resources/views/admin/chat.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/LivechatTest.php`, `tests/Feature/AdminPanelTest.php`

**Interfaces:**
- `GET admin.chat` renders the admin Chat nhanh page; JSON endpoints from Task 2 feed the customer list and selected conversation.
- The admin page uses DOM construction/textContent for customer names, preview, sender, and message content; Support Center markup/routes remain separate.

- [ ] **Step 1: Add failing admin page/XSS/responsive contract tests**

Assert a non-admin receives 403 from every admin chat route, an admin page contains `CHAT NHANH`, a nav link points to `admin.chat` (not a JSON endpoint), and unsafe customer/message values are not inserted through raw `innerHTML`.

- [ ] **Step 2: Run the tests and verify they fail because the admin page/route/nav is absent**

Run: `php artisan test tests/Feature/LivechatTest.php tests/Feature/AdminPanelTest.php --filter='chat|Chat|admin'`

Expected: FAIL with missing route/view markers.

- [ ] **Step 3: Implement the admin page, navigation, polling, and safe rendering**

Add a clear `Chat nhanh` sidebar link to the HTML admin page. Build a two-panel desktop layout that becomes one column on narrow screens. Fetch customers and selected conversations with `response.ok` checks, show Vietnamese errors, mark read on open through the API, poll only while the page is visible, and prevent overlapping list/history requests. Use initials when no avatar exists and display unread counts.

- [ ] **Step 4: Run admin tests and verify they pass**

Run: `php artisan test tests/Feature/LivechatTest.php tests/Feature/AdminPanelTest.php --filter='chat|Chat|admin'`

Expected: PASS.

### Task 5: Add Lab 7 traceability and full regression coverage

**Files:**
- Create: `docs/LAB07_COMPLETION.md`
- Modify: `tests/Feature/LivechatTest.php`

- [ ] **Step 1: Add tests for remaining acceptance cases**

Cover customer replies reaching admin, admin replies reaching customer, no arbitrary receiver selection, empty and 5,001-character rejection, read/unread idempotency in both directions, XSS as plain text, existing Support Center create/reply/status/order behavior, and routes staying distinct.

- [ ] **Step 2: Run the complete focused feature file and verify it passes**

Run: `php artisan test tests/Feature/LivechatTest.php`

Expected: PASS with all Lab 7 assertions.

- [ ] **Step 3: Write the completion matrix**

Create `docs/LAB07_COMPLETION.md` with the exact columns `Yêu cầu Lab 7`, `Trạng thái`, `FIELDCRAFT implementation`, `Route/File`, and `Cách test`, with rows for storage, customer/admin flows, send/history/list, popup, admin UI, polling, read/unread, and authorization/security.

### Task 6: Verify, migrate, and commit

**Files:**
- Verify all changed files; do not change unrelated features.

- [ ] **Step 1: Run safe migration**

Run: `php artisan migrate --force`

Expected: migration creates only the additive `messages` table or reports nothing to migrate; never run a destructive reset.

- [ ] **Step 2: Run full tests and route audit**

Run: `php artisan test` and `php artisan route:list`.

Expected: zero test failures; route list contains customer/admin chat routes and the existing Support Center routes.

- [ ] **Step 3: Run diff and working-tree checks**

Run: `git diff --check` and `git status --short`.

Expected: diff check exits 0; only intended Lab 7 files are changed.

- [ ] **Step 4: Commit the complete implementation**

```powershell
git add .
git commit -m "Complete Lab 7 customer admin livechat"
```

Return the commit hash, test/assertion counts, migration result, route names, changed files, and manual customer/admin test steps.
