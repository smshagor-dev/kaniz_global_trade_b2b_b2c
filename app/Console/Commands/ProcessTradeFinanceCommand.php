<?php

namespace App\Console\Commands;

use App\Services\B2BTradeFinanceService;
use Illuminate\Console\Command;

class ProcessTradeFinanceCommand extends Command
{
    protected $signature = 'b2b:trade-finance:process';

    protected $description = 'Process scheduled trade finance releases and escrow expiries';

    public function handle(B2BTradeFinanceService $tradeFinanceService): int
    {
        $result = $tradeFinanceService->processDueReleasesAndExpiries();
        $this->info('Processed trade finance operations. Released milestones: '.$result['released'].', expired escrows: '.$result['expired']);

        return self::SUCCESS;
    }
}
