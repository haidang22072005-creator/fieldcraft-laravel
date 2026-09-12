<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MatchdayCampaign extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'approved_at' => 'datetime']; }
    public function teamProfile(): BelongsTo { return $this->belongsTo(TeamProfile::class); }
    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function products(): BelongsToMany { return $this->belongsToMany(Product::class, 'matchday_campaign_product'); }
}
