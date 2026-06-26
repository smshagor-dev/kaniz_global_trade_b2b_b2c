<?php

namespace App\Services;

use App\Models\B2BAuditLog;
use Illuminate\Database\Eloquent\Model;

class B2BAuditService
{
    public function log(?int $actorUserId, ?int $actorCompanyId, string $eventType, Model $auditable, ?string $description = null, array $meta = []): void
    {
        B2BAuditLog::create([
            'actor_user_id' => $actorUserId,
            'actor_company_id' => $actorCompanyId,
            'event_type' => $eventType,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
