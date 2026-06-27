<?php

namespace App\Console\Commands;

use App\Services\Search\SearchService;
use Illuminate\Console\Command;

class SearchRetryFailedCommand extends Command
{
    protected $signature = 'search:retry-failed
        {--queue : Dispatch retry work to the queue}
        {--run= : Retry failures for a specific search indexing run}';

    protected $description = 'Retry unresolved enterprise search indexing failures.';

    public function handle(SearchService $searchService): int
    {
        $count = $searchService->retryFailures((bool) $this->option('queue'), $this->option('run') ? (int) $this->option('run') : null);
        $this->info('Retried ' . $count . ' failed search indexing records.');

        return self::SUCCESS;
    }
}
