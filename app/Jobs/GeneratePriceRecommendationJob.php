<?php

namespace App\Jobs;

use App\Services\AI\AIPriceRecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePriceRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload, public ?int $companyId = null)
    {
    }

    public function handle(AIPriceRecommendationService $service): void
    {
        $service->recommend($this->payload, null, $this->companyId);
    }
}
