<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Actions\CancelOrder;
use App\Exceptions\GHNException;
use App\Exceptions\MoMoInitializationException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CartManager;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\MoMoService;
use App\Services\PayOSService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(private CartManager $cart) {}

    public function create(Request $request): View|RedirectResponse
    {
        $cartItems = $this->cart->items($request);
        if ($cartItems->contains(fn (array $line) => $line['selection_requested'] && ! $line['available'])) return redirect()->route('cart.index')->withErrors(['cart' => 'Tồn kho đã thay đổi. Vui lòng chỉnh lại số lượng trước khi thanh toán.']);
        $items = $cartItems->where('selected', true)->values();
        if ($items->isEmpty()) return redirect()->route('cart.index')->withErrors(['cart' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.']);
        $user = $request->user();
        $address = $user->addresses()->orderByDesc('is_default')->latest()->first();
        $defaults = [
            'recipient_name' => $address?->recipient_name ?: $user->name,
            'recipient_phone' => $address?->phone ?: $user->phone,
            'recipient_email' => $user->email,
            'province' => $address?->province_code,
            'district' => null,
            'ward' => $address?->ward_code,
            'address_line' => $address?->address_line,
        ];
        return view('checkout', compact('items', 'defaults'));
    }

    public function store(Request $request, CreateOrder $createOrder, GHNService $ghn, GHNOrderService $ghnOrders, MoMoService $momo, PayOSService $payOS, CancelOrder $cancelOrder): RedirectResponse
    {
        $input = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'recipient_phone' => ['required', 'regex:/^0\d{9}$/'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'province' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'ward' => ['required', 'string', 'max:100'],
            'to_district_id' => ['nullable', 'integer', 'min:1'],
            'to_ward_code' => ['nullable', 'string', 'max:20'],
            'address_line' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,momo,bank_qr'],
            'coupon' => ['nullable', 'string', 'max:30'],
        ]);
        $cartItems = $this->cart->items($request);
        if ($cartItems->contains(fn (array $line) => $line['selection_requested'] && ! $line['available'])) return redirect()->route('cart.index')->withErrors(['cart' => 'Tồn kho đã thay đổi. Vui lòng chỉnh lại số lượng.']);
        $items = $cartItems->where('selected', true)->values();
        if ($items->isEmpty()) return redirect()->route('cart.index')->withErrors(['cart' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.']);

        $toDistrictId = $input['to_district_id'] ?? null;
        $toWardCode = $input['to_ward_code'] ?? null;
        if (! $toDistrictId && ctype_digit((string) $input['district'])) {
            $toDistrictId = (int) $input['district'];
        }
        if (! $toWardCode && ctype_digit((string) $input['ward'])) {
            $toWardCode = $input['ward'];
        }

        $shippingFee = 0;
        if ($toDistrictId || $toWardCode) {
            if (! $toDistrictId || ! $toWardCode) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'to_district_id' => 'Vui lòng chọn đầy đủ quận/huyện và phường/xã GHN.',
                ]);
            }
            try {
                $feeData = $ghn->calculateFee(
                    (int) $toDistrictId,
                    (string) $toWardCode,
                    $ghnOrders->weightForCartItems($items),
                );
            } catch (GHNException) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'shipping' => 'Không thể tính phí giao hàng lúc này. Vui lòng thử lại.',
                ]);
            }
            $shippingFee = (int) ($feeData['total'] ?? $feeData['service_fee'] ?? $feeData['fee'] ?? 0);
        }

        [$order, $payment] = DB::transaction(function () use ($createOrder, $items, $input, $request, $shippingFee, $toDistrictId, $toWardCode) {
            $order = $createOrder->handle($items->map(fn ($line) => ['product_variant_id'=>$line['variant']->id,'quantity'=>$line['quantity']]), [
                'user_id' => $request->user()?->id,
                'payment_method' => $input['payment_method'],
                'payment_status' => $input['payment_method'] === 'cod' ? 'unpaid' : 'pending',
                'status' => $input['payment_method'] === 'cod' ? 'pending' : 'pending_payment',
                'shipping_fee' => $shippingFee,
                'coupon_code' => $input['coupon'] ?? null,
                'recipient_name' => $input['recipient_name'],
                'recipient_phone' => $input['recipient_phone'],
                'recipient_email' => $input['recipient_email'],
                'province' => $input['province'],
                'district' => $input['district'],
                'ward' => $input['ward'],
                'to_district_id' => $toDistrictId,
                'to_ward_code' => $toWardCode,
                'address_line' => $input['address_line'],
                'note' => $input['note'] ?? null,
            ]);
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => $input['payment_method'],
                'request_id' => (string) Str::uuid(),
                'amount' => $order->total,
                'status' => $input['payment_method'] === 'cod' ? 'unpaid' : 'pending',
            ]);
            $providerOrderId = $input['payment_method'] === 'bank_qr'
                ? now()->format('ymdHis').str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT)
                : $order->number.'-P'.$payment->id;
            $payment->update(['provider_order_id' => $providerOrderId]);
            return [$order, $payment];
        });
        app(\App\Services\AdminNotificationService::class)->notify('new_order', 'Có đơn hàng mới', $order->number, [], $order);
        app(\App\Services\ActivityLogService::class)->record('order.created', $order, ['payment_method' => $order->payment_method], $request->user()->id);

        if ($payment->provider === 'momo') {
            try {
                $response = $momo->createPayment($order, $payment);
                $payment->update([
                    'pay_url' => $response['payUrl'],
                    'result_code' => $response['resultCode'] ?? 0,
                    'message' => $response['message'] ?? null,
                ]);
                $this->cart->removePurchased($request, $items);
                return redirect()->away($response['payUrl']);
            } catch (MoMoInitializationException $exception) {
                $payment->update(['status' => 'failed', 'result_code' => $exception->resultCode, 'message' => $exception->providerMessage]);
                $cancelOrder->handle($order, 'failed');
                return redirect()->route('checkout')->withErrors(['payment_method' => 'Không thể khởi tạo thanh toán MoMo. Vui lòng thử lại.']);
            } catch (Throwable) {
                $payment->update(['status' => 'failed', 'message' => 'Không thể khởi tạo thanh toán MoMo.']);
                $cancelOrder->handle($order, 'failed');
                return redirect()->route('checkout')->withErrors(['payment_method' => 'Không thể khởi tạo thanh toán MoMo. Vui lòng thử lại.']);
            }
        }

        if ($payment->provider === 'bank_qr') {
            try {
                $response = $payOS->createPayment($order, $payment);
                $payment->update([
                    'pay_url' => $response['checkoutUrl'],
                    'transaction_id' => $response['paymentLinkId'] ?? null,
                    'result_code' => 0,
                    'message' => $response['status'] ?? 'PENDING',
                ]);
                $this->cart->removePurchased($request, $items);
                return redirect()->away($response['checkoutUrl']);
            } catch (Throwable) {
                $payment->update(['status' => 'failed', 'message' => 'Không thể khởi tạo thanh toán payOS.']);
                $cancelOrder->handle($order, 'failed');
                return redirect()->route('checkout')->withErrors(['payment_method' => 'Không thể khởi tạo thanh toán payOS. Vui lòng thử lại.']);
            }
        }

        if ($toDistrictId && $toWardCode && $ghn->isConfigured()) {
            try {
                $ghnOrders->createAndStoreWaybill($order);
            } catch (GHNException) {
                // The paid/local order is still valid when GHN waybill creation is unavailable.
            }
        }
        $this->cart->removePurchased($request, $items);
        return redirect()->route('store.home')->with('success', "Đặt hàng thành công. Mã đơn: {$order->number}");
    }

    public function purchases(Request $request): View
    {
        return view('purchases', ['orders' => $request->user()->orders()->with(['items', 'payments'])->latest()->get()]);
    }

    public function showBankPayment(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load(['payments', 'items']);
        $payment = $order->payments()->latest('id')->first();
        return view('payments.bank-qr', compact('order', 'payment'));
    }
}
