<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['admin_replied_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }

    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }

    public function adminRepliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_replied_by');
    }
}
