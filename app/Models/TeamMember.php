<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $guarded = [];

    public function teamProfile(): BelongsTo { return $this->belongsTo(TeamProfile::class); }
}
