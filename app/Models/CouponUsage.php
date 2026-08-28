<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
