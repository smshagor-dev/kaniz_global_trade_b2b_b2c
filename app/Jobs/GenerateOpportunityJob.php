<?php

namespace App\Jobs;

use App\Models\B2BCompany;
use App\Services\AI\AITradeOpportunityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateOpportunityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $companyId)
    {
    }

    public function handle(AITradeOpportunityService $service): void
    {
        $service->detectForCompany(B2BCompany::findOrFail($this->companyId));
    }
}
