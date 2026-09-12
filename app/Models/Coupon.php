<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['expires_at' => 'datetime', 'starts_at' => 'datetime', 'is_active' => 'boolean']; }
    public function usages(): HasMany { return $this->hasMany(CouponUsage::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
