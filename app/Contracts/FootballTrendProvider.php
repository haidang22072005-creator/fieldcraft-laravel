<?php

namespace App\Contracts;

interface FootballTrendProvider
{
    public function fetch(array $filters = []): array;
    public function connected(): bool;
    public function name(): string;
}
