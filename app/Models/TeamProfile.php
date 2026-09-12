<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamProfile extends Model
{
    protected $guarded = [];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function orderDrafts(): HasMany
    {
        return $this->hasMany(TeamOrderDraft::class);
    }

    public function campaigns(): HasMany { return $this->hasMany(MatchdayCampaign::class); }
}
