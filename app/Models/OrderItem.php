<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $guarded = [];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function customizationJobs(): HasMany { return $this->hasMany(CustomizationJob::class); }
}
