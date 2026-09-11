<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayOSService
{
    public function createPayment(Order $order, Payment $payment): array
    {
        $clientId = (string) config('services.payos.client_id');
        $apiKey = (string) config('services.payos.api_key');
        $checksumKey = (string) config('services.payos.checksum_key');
        $returnUrl = (string) (config('services.payos.return_url') ?: route('payos.return'));
        $cancelUrl = (string) (config('services.payos.cancel_url') ?: route('payos.cancel'));
        $orderCode = (string) $payment->provider_order_id;

        if ($clientId === '' || $apiKey === '' || $checksumKey === '' || ! ctype_digit($orderCode)
            || $payment->order_id !== $order->id || (int) $payment->amount !== (int) $order->total) {
            throw new RuntimeException('payOS is not configured or payment data is invalid.');
        }

        $data = [
            'orderCode' => (int) $orderCode,
            'amount' => (int) $order->total,
            'description' => 'FC'.substr($orderCode, -7),
            'buyerName' => $order->recipient_name,
            'buyerEmail' => $order->recipient_email,
            'buyerPhone' => $order->recipient_phone,
            'buyerAddress' => $order->address_line,
            'returnUrl' => $returnUrl,
            'cancelUrl' => $cancelUrl,
        ];
        $data['signature'] = hash_hmac('sha256', implode('&', [
            'amount='.$data['amount'], 'cancelUrl='.$cancelUrl, 'description='.$data['description'],
            'orderCode='.$data['orderCode'], 'returnUrl='.$returnUrl,
        ]), $checksumKey);

        $response = Http::acceptJson()->asJson()->withHeaders(['x-client-id' => $clientId, 'x-api-key' => $apiKey])
            ->timeout(30)->post(rtrim((string) config('services.payos.base_url'), '/').'/v2/payment-requests', $data);
        $body = $response->json();
        $result = is_array($body) && is_array($body['data'] ?? null) ? $body['data'] : [];
        if ($response->failed() || ($body['code'] ?? null) !== '00' || blank($result['checkoutUrl'] ?? null)) {
            throw new RuntimeException('payOS payment initialization failed.');
        }
        return $result;
    }

    public function verifyWebhook(array $payload): bool
    {
        $data = $payload['data'] ?? null;
        $signature = $payload['signature'] ?? null;
        $key = (string) config('services.payos.checksum_key');
        if (! is_array($data) || ! is_string($signature) || $signature === '' || $key === '') return false;
        return hash_equals(hash_hmac('sha256', $this->canonical($data), $key), $signature);
    }

    public function signWebhookData(array $data): string
    {
        $key = (string) config('services.payos.checksum_key');
        if ($key === '') throw new RuntimeException('payOS signing is not configured.');
        return hash_hmac('sha256', $this->canonical($data), $key);
    }

    private function canonical(array $data): string
    {
        ksort($data);
        return collect($data)->map(function ($value, $key) {
            if ($value === null) $value = '';
            elseif (is_bool($value)) $value = $value ? 'true' : 'false';
            elseif (is_array($value) || is_object($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $key.'='.$value;
        })->implode('&');
    }
}
