<?php

namespace App\Console\Commands;

use App\Services\Currency\CurrencyService;
use Illuminate\Console\Command;

class CurrencySyncCommand extends Command
{
    protected $signature = 'currency:sync {--force : Run the sync even if the configured cadence is not due}';

    protected $description = 'Sync global currency exchange rates using the configured provider';

    public function handle(CurrencyService $currencyService): int
    {
        $result = $currencyService->sync((bool) $this->option('force'));

        $this->line($result['message'] ?? 'Currency sync finished.');

        return ($result['status'] ?? 'failed') === 'failed'
            ? self::FAILURE
            : self::SUCCESS;
    }
}
