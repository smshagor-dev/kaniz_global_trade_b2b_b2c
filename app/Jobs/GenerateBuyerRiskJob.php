<?php

namespace App\Jobs;

use App\Models\B2BCompany;
use App\Services\AI\AIBuyerRiskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBuyerRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $buyerCompanyId, public ?int $companyId = null)
    {
    }

    public function handle(AIBuyerRiskService $service): void
    {
        $service->assess(B2BCompany::findOrFail($this->buyerCompanyId), null, $this->companyId);
    }
}
