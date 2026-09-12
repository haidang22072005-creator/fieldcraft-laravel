<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BootPassport extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['variant_snapshot' => 'array', 'purchase_date' => 'date', 'warranty_until' => 'date', 'second_hand_eligible' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}
