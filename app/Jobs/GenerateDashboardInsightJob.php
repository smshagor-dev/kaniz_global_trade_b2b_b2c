<?php

namespace App\Jobs;

use App\Models\B2BCompany;
use App\Services\AI\AIDashboardInsightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDashboardInsightJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $companyId)
    {
    }

    public function handle(AIDashboardInsightService $service): void
    {
        $company = B2BCompany::findOrFail($this->companyId);
        $service->generateForCompany($company);
    }
}
