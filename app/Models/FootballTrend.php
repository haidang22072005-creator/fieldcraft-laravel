<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FootballTrend extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['trend_data' => 'array']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
