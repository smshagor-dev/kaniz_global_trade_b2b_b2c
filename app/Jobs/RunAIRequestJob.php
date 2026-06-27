<?php

namespace App\Jobs;

use App\Services\AI\AIRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunAIRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $payload)
    {
    }

    public function handle(AIRequestService $requestService): void
    {
        $requestService->request($this->payload);
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
