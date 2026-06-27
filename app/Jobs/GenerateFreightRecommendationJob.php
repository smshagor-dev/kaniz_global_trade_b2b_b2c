<?php

namespace App\Jobs;

use App\Models\B2BFreightQuote;
use App\Services\AI\AIFreightRecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateFreightRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $freightQuoteId, public ?int $companyId = null)
    {
    }

    public function handle(AIFreightRecommendationService $service): void
    {
        $service->recommendForQuote(B2BFreightQuote::findOrFail($this->freightQuoteId), null, $this->companyId);
    }
}
