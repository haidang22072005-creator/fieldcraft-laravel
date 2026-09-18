# FIELDCRAFT Lab 7 — Tin nhắn / Livechat

| Yêu cầu Lab 7 | Trạng thái | FIELDCRAFT implementation | Route/File | Cách test |
|---|---|---|---|---|
| Message storage | Hoàn tất | Bảng `messages`, `Message` model, foreign keys, indexes, `is_read`, timestamp | `database/migrations/2026_09_18_000100_create_messages_table.php`, `app/Models/Message.php` | `LivechatTest::test_direct_messages_persist_with_sender_receiver_and_read_state` |
| Customer controller/flow | Hoàn tất | API chỉ dùng customer đang đăng nhập; chọn admin từ database | `app/Http/Controllers/ChatMessageController.php`, `app/Services/ChatMessageService.php` | `LivechatTest::test_customer_sends_to_a_database_selected_admin_and_admin_replies` |
| Admin controller/flow | Hoàn tất | Admin middleware, target phải có role customer, reply từ admin hiện tại | `app/Http/Controllers/Admin/ChatMessageController.php` | `LivechatTest::test_chat_authorization_validation_and_missing_admin_are_truthful` |
| Customer send | Hoàn tất | POST content tối đa 5.000 ký tự, không nhận receiver tùy ý | `POST /chat/messages` · `chat.messages.store` | Customer send/reply test |
| Customer history | Hoàn tất | Tối đa 100 tin mới nhất, trả cũ → mới; đánh dấu admin messages đã đọc | `GET /chat/messages` · `chat.messages.index` | Bounded chronological/read test |
| Admin user list | Hoàn tất | Chỉ khách có hội thoại, preview/time/unread count bằng query database | `GET /admin/chat/customers` · `admin.chat.customers.index` | Admin list test |
| Admin history | Hoàn tất | Lịch sử bounded, eager-load sender/receiver, đánh dấu customer messages đã đọc | `GET /admin/chat/customers/{user}/messages` | Admin read/ownership test |
| Admin send | Hoàn tất | Reply tới customer route-bound, không IDOR | `POST /admin/chat/customers/{user}/messages` | Customer/admin send test |
| Popup customer | Hoàn tất | Floating `💬 HỖ TRỢ TRỰC TUYẾN`, responsive popup, loading/error states | `resources/views/components/livechat.blade.php`, storefront/settings includes | Customer surfaces contract test |
| Admin chat UI | Hoàn tất | Hai panel desktop, một cột mobile, `CHAT NHANH`, Support Center riêng | `resources/views/admin/chat.blade.php`, `resources/views/layouts/admin.blade.php` | Admin page/nav tests |
| Polling | Hoàn tất | Poll khoảng 3 giây khi popup/page hiển thị, guard chống overlap; customer dừng khi đóng/ẩn | Customer component/admin chat page JS | UI contract review; API feature coverage |
| Read/unread | Hoàn tất | Update có điều kiện `is_read=false`, idempotent, đúng hướng hội thoại | `ChatMessageService` | Bounded/read and admin-open tests |
| Authorization/security | Hoàn tất | Không hard-code admin ID, target customer kiểm tra role, không raw HTML cho dữ liệu chat | Controllers, service, Blade JS | Authorization + plain-text/XSS test |
| Support Center regression | Hoàn tất | Giữ nguyên `support_tickets`/`support_messages` và route family | `SupportTicketController`, existing support tests | `CustomerExperienceBackendTest`, livechat coexistence test |

## Manual smoke test

1. Chạy `php artisan migrate` và đăng nhập một tài khoản customer cùng một tài khoản admin.
2. Mở storefront hoặc Settings bằng customer, bấm `💬 HỖ TRỢ TRỰC TUYẾN`, gửi tin nhắn; xác nhận trạng thái loading/error và tin nhắn xuất hiện ở cuối hội thoại.
3. Mở `/admin/chat` bằng admin, chọn khách trong `CHAT NHANH`, xác nhận unread badge giảm khi mở, trả lời và chờ polling.
4. Quay lại customer, xác nhận reply admin xuất hiện; mở Support Center và tạo/reply ticket để xác nhận hai luồng độc lập.
