<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomizationJob extends Model
{
    protected $guarded = [];

    public const STATUSES = ['design_pending', 'customer_approval', 'approved', 'printing', 'quality_check', 'completed'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }

    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
