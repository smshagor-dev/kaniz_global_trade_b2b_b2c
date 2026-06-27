<?php

namespace App\Jobs;

use App\Models\B2BCompany;
use App\Services\AI\AISupplierRiskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSupplierRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $supplierCompanyId, public ?int $companyId = null)
    {
    }

    public function handle(AISupplierRiskService $service): void
    {
        $service->assess(B2BCompany::findOrFail($this->supplierCompanyId), null, $this->companyId);
    }
}
