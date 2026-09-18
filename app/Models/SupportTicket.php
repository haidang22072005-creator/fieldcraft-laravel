<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $guarded = [];

    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];
    public const CATEGORIES = ['order', 'payment', 'shipping', 'product', 'refund', 'account', 'other'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function messages(): HasMany { return $this->hasMany(SupportMessage::class, 'ticket_id'); }

    public function sortMessagesChronologically(): static
    {
        if ($this->relationLoaded('messages')) {
            $this->setRelation('messages', $this->messages->sortBy(fn ($message) => sprintf('%010d:%020d', $message->created_at?->getTimestamp() ?? 0, (int) $message->id))->values());
        }

        return $this;
    }
}
