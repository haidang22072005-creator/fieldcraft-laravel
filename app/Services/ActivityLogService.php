<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public function record(string $action, ?Model $subject = null, array $metadata = [], ?int $actorId = null): ActivityLog
    {
        return ActivityLog::create(['actor_id' => $actorId, 'action' => $action, 'subject_type' => $subject?->getMorphClass(), 'subject_id' => $subject?->getKey(), 'metadata' => $this->safe($metadata)]);
    }

    public function recordOnce(string $action, ?Model $subject = null, array $metadata = [], ?int $actorId = null): ?ActivityLog
    {
        if ($subject && ActivityLog::query()->where('action', $action)->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey())->exists()) return null;
        return $this->record($action, $subject, $metadata, $actorId);
    }

    private function safe(array $data): array
    {
        $blocked = ['password', 'token', 'secret', 'authorization', 'signature', 'api_key', 'access_key'];
        return collect($data)->mapWithKeys(function ($value, $key) use ($blocked) {
            if (in_array(strtolower((string) $key), $blocked, true)) return [$key => '[redacted]'];
            return [$key => is_array($value) ? $this->safe($value) : $value];
        })->all();
    }
}
