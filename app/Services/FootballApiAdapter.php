<?php

namespace App\Services;

use App\Contracts\FootballTrendProvider;

class FootballApiAdapter implements FootballTrendProvider
{
    public function fetch(array $filters = []): array
    {
        return ['available' => false, 'connected' => false, 'configured' => $this->configured(), 'provider' => $this->name(), 'message' => 'Nhà cung cấp bóng đá chưa có adapter vận hành; không có dữ liệu live.', 'data' => []];
    }
    public function configured(): bool { return filled(config('services.football.endpoint')) && filled(config('services.football.api_key')); }
    public function connected(): bool { return false; }
    public function name(): string { return (string) (config('services.football.provider') ?: 'not_connected'); }
}
