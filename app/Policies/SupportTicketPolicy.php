<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function before(User $user): ?bool
    {
        return in_array($user->role, ['admin', 'super-admin'], true) ? true : null;
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return (int) $ticket->user_id === (int) $user->id;
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return (int) $ticket->user_id === (int) $user->id;
    }
}
