<?php

namespace App\Console\Commands;

use App\Services\B2BInsuranceService;
use Illuminate\Console\Command;

class ProcessB2BInsuranceCommand extends Command
{
    protected $signature = 'b2b:insurance:process';

    protected $description = 'Process B2B insurance expiries and notifications';

    public function handle(B2BInsuranceService $insuranceService): int
    {
        $result = $insuranceService->processLifecycle();

        $this->info('Processed B2B insurance lifecycle.');
        $this->line('Expired: ' . $result['expired']);
        $this->line('Expiring soon: ' . $result['expiring_soon']);

        return self::SUCCESS;
    }
}
