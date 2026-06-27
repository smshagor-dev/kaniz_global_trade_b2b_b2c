<?php

namespace App\Jobs;

use App\Models\B2BInsuranceQuote;
use App\Services\B2BInsuranceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInsurancePolicyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int $quoteId,
        protected array $payload = []
    ) {
    }

    public function handle(B2BInsuranceService $insuranceService): void
    {
        $quote = B2BInsuranceQuote::with(['creator'])->find($this->quoteId);

        if (!$quote || $quote->policy()->exists()) {
            return;
        }

        $insuranceService->issuePolicy($quote, $this->payload, $quote->creator, $quote->buyer_company_id ?: $quote->supplier_company_id);
    }
}
