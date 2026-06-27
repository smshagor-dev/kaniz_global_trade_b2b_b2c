<?php

namespace App\Jobs;

use App\Models\B2BInsuranceClaim;
use App\Services\B2BInsuranceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyInsuranceClaimJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected int $claimId)
    {
    }

    public function handle(B2BInsuranceService $insuranceService): void
    {
        $claim = B2BInsuranceClaim::with(['documents', 'policy'])->find($this->claimId);

        if (!$claim) {
            return;
        }

        $result = $insuranceService->validateClaim($claim);
        $claim->update([
            'validation_summary' => $result['validation_summary'],
            'fraud_signals' => $result['fraud_signals'],
            'status' => in_array($claim->status, ['submitted', 'review'], true) ? 'investigation' : $claim->status,
        ]);
    }
}
