<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamOrderDraftItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['shirt_number' => 'integer', 'quantity' => 'integer', 'customization' => 'array'];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(TeamOrderDraft::class, 'team_order_draft_id');
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
