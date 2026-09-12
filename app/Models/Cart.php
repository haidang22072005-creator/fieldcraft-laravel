<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function contactedBy(): BelongsTo { return $this->belongsTo(User::class, 'contacted_by'); }
    public function contactCoupon(): BelongsTo { return $this->belongsTo(Coupon::class, 'contact_coupon_id'); }
}
