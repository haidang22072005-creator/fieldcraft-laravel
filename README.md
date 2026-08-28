# Fieldcraft — Laravel Storefront

Demo storefront cho dụng cụ bóng đá, thiết kế theo đặc tả: hero Messi, catalogue lọc không tải lại trang, quick add / giỏ hàng / voucher và admin dashboard preview.

## Chạy local

```powershell
cd outputs/fieldcraft-laravel
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Truy cập `http://127.0.0.1:8000`; bản xem dashboard ở `/admin-preview`.

## Quản trị

Vào `/login` bằng `admin@fieldcraft.vn` / `Admin@12345`, sau đó quản lý sản phẩm, upload ảnh, biến thể/kho, đơn hàng và mã giảm giá ở `/admin`.

## Roadmap backend cần kết nối DB

- `products`, `product_variants`, `product_images`, `carts`, `cart_items`, `orders`, `order_items`, `addresses`, `coupons`.
- Checkout cần bọc trong `DB::transaction()` và khóa tồn kho variant trước khi tạo đơn.
- Bảo vệ dashboard production bằng `auth` và `role:super-admin` middleware (preview hiện mở riêng để duyệt UI).
