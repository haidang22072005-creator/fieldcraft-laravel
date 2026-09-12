<?php

namespace App\Services;

use App\Contracts\FootballTrendProvider;

class FootballApiAdapter implements FootballTrendProvider
{
    public function fetch(array $filters = []): array
    {
        if (! $this->connected()) return ['available' => false, 'provider' => $this->name(), 'message' => 'Chưa kết nối nhà cung cấp dữ liệu bóng đá.', 'data' => []];
        return ['available' => false, 'provider' => $this->name(), 'message' => 'Adapter đã sẵn sàng nhưng chưa có bộ chuyển đổi dữ liệu của nhà cung cấp.', 'data' => []];
    }
    public function connected(): bool { return filled(config('services.football.endpoint')) && filled(config('services.football.api_key')); }
    public function name(): string { return (string) (config('services.football.provider') ?: 'not_connected'); }
}
