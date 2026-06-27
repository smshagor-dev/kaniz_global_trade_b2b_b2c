<?php

namespace App\Jobs;

use App\Services\AI\AICurrencyAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCurrencyAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $currencyCode, public float $amount, public ?int $companyId = null)
    {
    }

    public function handle(AICurrencyAnalysisService $service): void
    {
        $service->analyze($this->currencyCode, $this->amount, null, $this->companyId);
    }
}
