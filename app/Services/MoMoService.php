<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MoMoService
{
    public function createPayment(Order $order, Payment $payment): array
    {
        $partnerCode = (string) config('services.momo.partner_code');
        $accessKey = (string) config('services.momo.access_key');
        $secretKey = (string) config('services.momo.secret_key');
        $redirectUrl = (string) (config('services.momo.redirect_url') ?: route('momo.return'));
        $ipnUrl = (string) (config('services.momo.ipn_url') ?: route('momo.ipn'));

        if ($partnerCode === '' || $accessKey === '' || $secretKey === '' || $payment->order_id !== $order->id || (int) $payment->amount !== (int) $order->total) {
            throw new RuntimeException('MoMo is not configured or payment data is invalid.');
        }

        $data = [
            'partnerCode' => $partnerCode,
            'requestId' => $payment->request_id,
            'amount' => (string) $order->total,
            'orderId' => $order->number,
            'orderInfo' => 'Thanh toán đơn hàng '.$order->number,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'requestType' => 'payWithCC',
            'extraData' => '',
            'lang' => 'vi',
            'autoCapture' => true,
        ];
        $data['signature'] = hash_hmac('sha256', $this->createRawSignature($data, $accessKey), $secretKey);

        $response = Http::acceptJson()->asJson()->timeout(30)
            ->post((string) config('services.momo.endpoint'), $data);
        $body = $response->json();

        if ($response->failed() || ! is_array($body) || (int) ($body['resultCode'] ?? -1) !== 0 || blank($body['payUrl'] ?? null)) {
            throw new RuntimeException('MoMo payment initialization failed.');
        }

        return $body;
    }

    public function verifySignature(array $payload): bool
    {
        $provided = (string) ($payload['signature'] ?? '');
        $secretKey = (string) config('services.momo.secret_key');
        $accessKey = (string) config('services.momo.access_key');
        if ($provided === '' || $secretKey === '' || $accessKey === '') {
            return false;
        }

        $keys = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];
        $parts = ['accessKey='.$accessKey];
        foreach ($keys as $key) {
            $parts[] = $key.'='.(string) ($payload[$key] ?? '');
        }

        return hash_equals(hash_hmac('sha256', implode('&', $parts), $secretKey), $provided);
    }

    private function createRawSignature(array $data, string $accessKey): string
    {
        return implode('&', [
            'accessKey='.$accessKey,
            'amount='.$data['amount'],
            'extraData='.$data['extraData'],
            'ipnUrl='.$data['ipnUrl'],
            'orderId='.$data['orderId'],
            'orderInfo='.$data['orderInfo'],
            'partnerCode='.$data['partnerCode'],
            'redirectUrl='.$data['redirectUrl'],
            'requestId='.$data['requestId'],
            'requestType='.$data['requestType'],
        ]);
    }
}
