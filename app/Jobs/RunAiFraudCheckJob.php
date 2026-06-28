<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Fraud\FraudScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunAiFraudCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $userId)
    {
    }

    public function handle(FraudScoringService $fraudScoringService): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $fraudScoringService->runForUser($user, [
            'run_ai' => true,
            'event_type' => 'ai_fraud_check_ran',
            'reason' => 'AI fraud check triggered.',
        ]);
    }
}
