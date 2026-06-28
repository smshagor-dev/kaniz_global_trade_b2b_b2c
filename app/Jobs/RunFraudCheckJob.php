<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Fraud\FraudScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunFraudCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $userId,
        protected array $options = []
    ) {
    }

    public function handle(FraudScoringService $fraudScoringService): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $fraudScoringService->runForUser($user, $this->options);
    }
}
