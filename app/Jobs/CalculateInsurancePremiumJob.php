<?php

namespace App\Jobs;

use App\Models\B2BInsuranceQuote;
use App\Services\B2BInsuranceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateInsurancePremiumJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected int $quoteId)
    {
    }

    public function handle(B2BInsuranceService $insuranceService): void
    {
        $quote = B2BInsuranceQuote::find($this->quoteId);

        if (!$quote) {
            return;
        }

        $insuranceService->recalculateQuote($quote->fresh(['creator', 'buyerCompany', 'supplierCompany']));
    }
}
