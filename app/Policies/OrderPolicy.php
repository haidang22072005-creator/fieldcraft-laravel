<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user): ?bool
    {
        return in_array($user->role, ['admin', 'super-admin'], true) ? true : null;
    }

    public function view(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->id;
    }
}
