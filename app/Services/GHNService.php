<?php

namespace App\Services;

use App\Exceptions\GHNException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GHNService
{
    public function isConfigured(): bool
    {
        return filled(config('services.ghn.token'))
            && filled(config('services.ghn.shop_id'))
            && filled(config('services.ghn.base_url'));
    }

    public function getProvinces(): array
    {
        return $this->request('get', '/master-data/province');
    }

    public function getDistricts(int $provinceId): array
    {
        return $this->request('get', '/master-data/district', ['province_id' => $provinceId]);
    }

    public function getWards(int $districtId): array
    {
        return $this->request('get', '/master-data/ward', ['district_id' => $districtId]);
    }

    public function calculateFee(int $toDistrictId, string $toWardCode, int $weight, array $options = []): array
    {
        $payload = array_merge([
            'service_type_id' => (int) config('services.ghn.service_type_id', 2),
            'from_district_id' => (int) config('services.ghn.from_district_id'),
            'from_ward_code' => (string) config('services.ghn.from_ward_code'),
            'to_district_id' => $toDistrictId,
            'to_ward_code' => $toWardCode,
            'weight' => max(1, $weight),
            'length' => 20,
            'width' => 20,
            'height' => 10,
            'insurance_value' => 0,
        ], $options);

        return $this->request('post', '/v2/shipping-order/fee', $payload, true);
    }

    public function createOrder(array $payload): array
    {
        return $this->request('post', '/v2/shipping-order/create', $payload, true);
    }

    public function cancelOrder(string $orderCode): array
    {
        return $this->request('post', '/v2/shipping-order/cancel', ['order_codes' => [$orderCode]]);
    }

    public function getOrderDetail(string $orderCode): array
    {
        return $this->request('post', '/v2/shipping-order/detail', ['order_code' => $orderCode]);
    }

    private function request(string $method, string $endpoint, array $payload = [], bool $requiresOrigin = false): array
    {
        if (! $this->isConfigured() || ($requiresOrigin && (
            ! filled(config('services.ghn.from_district_id')) ||
            ! filled(config('services.ghn.from_ward_code'))
        ))) {
            throw new GHNException('GHN shipping is not configured.');
        }

        try {
            $request = $this->client();
            $response = $method === 'get'
                ? $request->get($endpoint, $payload)
                : $request->post($endpoint, $payload);
            $body = $response->json();
            $body = is_array($body) ? $body : [];
            $ghnCode = $body['code'] ?? null;

            if ($response->failed() || ($ghnCode !== null && (int) $ghnCode !== 200)) {
                $this->logFailure(
                    $endpoint,
                    $response->status(),
                    $ghnCode,
                    $body['message'] ?? null,
                    $body['code_message'] ?? null,
                );
                throw new GHNException('GHN request failed.', $response->status());
            }

            $data = $body['data'] ?? [];
            return is_array($data) ? $data : [];
        } catch (GHNException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('GHN API request could not be completed.', [
                'endpoint' => $endpoint,
                'exception' => $exception::class,
            ]);
            throw new GHNException('GHN request failed.', null);
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.ghn.base_url'), '/'))
            ->withHeaders([
                'Token' => (string) config('services.ghn.token'),
                'ShopId' => (string) config('services.ghn.shop_id'),
                'Content-Type' => 'application/json',
            ])
            ->timeout((int) config('services.ghn.timeout', 15))
            ->withOptions(['verify' => (bool) config('services.ghn.verify_ssl', false)]);
    }

    private function logFailure(string $endpoint, int $status, mixed $ghnCode, mixed $message, mixed $codeMessage): void
    {
        Log::warning('GHN API returned an error.', [
            'endpoint' => $endpoint,
            'http_status' => $status,
            'ghn_code' => $ghnCode,
            'ghn_message' => $this->safeLogValue($message),
            'ghn_code_message' => $this->safeLogValue($codeMessage),
        ]);
    }

    private function safeLogValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $message = trim((string) $value);
        $token = (string) config('services.ghn.token');
        if ($token !== '') {
            $message = str_replace($token, '[redacted]', $message);
        }

        if (preg_match('/(?:token|authorization|credential|password|secret)\s*[:=]/i', $message)) {
            return '[redacted]';
        }

        return Str::limit($message, 200);
    }
}
