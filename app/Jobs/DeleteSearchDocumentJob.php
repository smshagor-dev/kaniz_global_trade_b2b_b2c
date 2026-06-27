<?php

namespace App\Jobs;

use App\Services\Search\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteSearchDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $modelClass, protected int $modelId)
    {
    }

    public function handle(SearchService $searchService): void
    {
        if (!class_exists($this->modelClass)) {
            return;
        }

        $model = new $this->modelClass();
        $model->setAttribute($model->getKeyName(), $this->modelId);

        $searchService->deleteModel($model);
    }
}
