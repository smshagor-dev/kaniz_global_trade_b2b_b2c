<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Fraud\FraudScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateUserRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $userId,
        protected string $eventType = 'risk_recalculated',
        protected string $reason = 'User activity changed.'
    ) {
    }

    public function handle(FraudScoringService $fraudScoringService): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $fraudScoringService->runForUser($user, [
            'event_type' => $this->eventType,
            'reason' => $this->reason,
        ]);
    }
}
