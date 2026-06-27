<?php

namespace App\Observers;

use App\Jobs\DeleteSearchDocumentJob;
use App\Jobs\SyncSearchDocumentJob;
use Illuminate\Database\Eloquent\Model;

class SearchIndexObserver
{
    public function saved(Model $model): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        SyncSearchDocumentJob::dispatch(get_class($model), $model->getKey())->onQueue(config('search.queue', 'default'));
    }

    public function deleted(Model $model): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DeleteSearchDocumentJob::dispatch(get_class($model), $model->getKey())->onQueue(config('search.queue', 'default'));
    }
}
