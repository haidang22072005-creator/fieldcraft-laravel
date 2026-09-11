<?php

namespace App\Services;

use App\Exceptions\MoMoInitializationException;
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
        $requestType = (string) config('services.momo.request_type', 'payWithCC');
        $gatewayOrderId = (string) $payment->provider_order_id;

        if ($partnerCode === '' || $accessKey === '' || $secretKey === '' || $gatewayOrderId === ''
            || ! in_array($requestType, ['payWithCC', 'payWithATM'], true)
            || $payment->order_id !== $order->id || (int) $payment->amount !== (int) $order->total) {
            throw new RuntimeException('MoMo is not configured or payment data is invalid.');
        }

        $data = [
            'partnerCode' => $partnerCode,
            'requestId' => $payment->request_id,
            'amount' => (string) $order->total,
            'orderId' => $gatewayOrderId,
            'orderInfo' => 'Thanh toán đơn hàng '.$order->number,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'requestType' => $requestType,
            'extraData' => '',
            'lang' => 'vi',
            'autoCapture' => true,
        ];
        $data['signature'] = hash_hmac('sha256', $this->createRawSignature($data, $accessKey), $secretKey);

        $response = Http::acceptJson()->asJson()->timeout(30)
            ->post((string) config('services.momo.endpoint'), $data);
        $body = $response->json();

        if ($response->failed() || ! is_array($body) || (int) ($body['resultCode'] ?? -1) !== 0 || blank($body['payUrl'] ?? null)
            || (isset($body['orderId']) && (string) $body['orderId'] !== $gatewayOrderId)) {
            $resultCode = is_numeric($body['resultCode'] ?? null) ? (int) $body['resultCode'] : null;
            $message = is_scalar($body['message'] ?? null) ? mb_substr((string) $body['message'], 0, 255) : null;
            throw new MoMoInitializationException($resultCode, $message);
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

        return hash_equals(hash_hmac('sha256', $this->resultRawSignature($payload, $accessKey), $secretKey), $provided);
    }

    public function signResultPayload(array $payload): array
    {
        $secretKey = (string) config('services.momo.secret_key');
        $accessKey = (string) config('services.momo.access_key');
        if ($secretKey === '' || $accessKey === '') {
            throw new RuntimeException('MoMo signing is not configured.');
        }
        $payload['signature'] = hash_hmac('sha256', $this->resultRawSignature($payload, $accessKey), $secretKey);
        return $payload;
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

    private function resultRawSignature(array $payload, string $accessKey): string
    {
        $keys = ['amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];
        $parts = ['accessKey='.$accessKey];
        foreach ($keys as $key) {
            $parts[] = $key.'='.(string) ($payload[$key] ?? '');
        }
        return implode('&', $parts);
    }
}
