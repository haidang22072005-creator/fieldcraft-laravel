<?php

namespace App\Policies;

use App\Models\SecondHandListing;
use App\Models\User;

class SecondHandListingPolicy
{
    public function before(User $user): ?bool
    {
        return in_array($user->role, ['admin', 'super-admin'], true) ? true : null;
    }

    public function view(User $user, SecondHandListing $listing): bool
    {
        return (int) $listing->user_id === (int) $user->id;
    }

    public function update(User $user, SecondHandListing $listing): bool
    {
        return (int) $listing->user_id === (int) $user->id && in_array($listing->status, ['submitted', 'under_review'], true);
    }
}
