# Fieldcraft — cửa hàng dụng cụ bóng đá

Ứng dụng Laravel 12 gồm catalogue sản phẩm, biến thể màu/size, giỏ hàng, checkout COD, đơn đã mua, hồ sơ và khu vực quản trị.

## Chức năng

- Khách vãng lai dùng giỏ hàng session; khách đăng nhập dùng giỏ hàng database. Giỏ session được gộp khi đăng nhập, không vượt tồn kho.
- Checkout yêu cầu đăng nhập và xác thực email. Giá, tổng tiền, tồn kho và coupon được kiểm tra phía server trong một transaction có khóa variant/coupon.
- Mã đơn sinh từ ID database. Coupon hỗ trợ tổng lượt dùng, giới hạn mỗi khách, lịch sử sử dụng và hoàn lượt đúng một lần khi hủy đơn.
- Quản trị viên quản lý sản phẩm/ảnh/biến thể/kho, coupon, khách hàng và trạng thái đơn tại `/admin` sau khi đăng nhập.

## Cài đặt và chạy

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Mở `http://127.0.0.1:8000`. Các route chính: `/`, `/cart`, `/checkout`, `/purchases`, `/settings`, `/login`, `/register`, `/admin`.

## Email

Local mặc định dùng `MAIL_MAILER=log`; liên kết xác thực được ghi vào `storage/logs/laravel.log`.

Nếu giảng viên yêu cầu gửi Gmail thật, tạo App Password trong tài khoản Google rồi chỉ cấu hình trong `.env` cục bộ: mailer `smtp`, host `smtp.gmail.com`, port `587`, username là email, password là App Password, mã hóa TLS và địa chỉ gửi. Không đưa mật khẩu hoặc `.env` lên Git.

## Kiểm tra

```powershell
php artisan migrate:status
php artisan route:list
php artisan test
git diff --check
```

Các form dùng CSRF và validation backend; xác thực email dùng URL ký số/throttle; checkout dùng `auth` + `verified`; quản trị dùng `auth` + `role:super-admin`.
