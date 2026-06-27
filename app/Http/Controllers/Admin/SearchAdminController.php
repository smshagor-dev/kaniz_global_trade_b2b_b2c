<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\SearchDocument;
use App\Models\SearchIndexingFailure;
use App\Models\SearchIndexingRun;
use App\Services\Search\SearchManager;
use App\Services\Search\SearchModelRegistry;
use App\Services\Search\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SearchAdminController extends Controller
{
    public function __construct(
        protected SearchService $searchService,
        protected SearchManager $searchManager
    ) {
    }

    public function dashboard()
    {
        $provider = $this->searchManager->activeProvider();

        return view('backend.search.dashboard', [
            'provider' => $provider,
            'providers' => array_keys((array) config('search.providers', [])),
            'indexName' => $this->searchManager->indexName(),
            'health' => $this->searchManager->resilientDriver($provider)->health($this->searchManager->indexName()),
            'documentCounts' => SearchDocument::query()
                ->selectRaw('type, COUNT(*) as total')
                ->groupBy('type')
                ->orderBy('type')
                ->get(),
            'failures' => SearchIndexingFailure::query()
                ->whereNull('resolved_at')
                ->latest('failed_at')
                ->paginate(15),
            'entityOptions' => SearchModelRegistry::entityOptions(),
            'latestRun' => SearchIndexingRun::query()->latest('id')->first(),
        ]);
    }

    public function analytics()
    {
        return view('backend.search.analytics', [
            'summary' => $this->searchService->analyticsSummary(),
            'recentFailures' => SearchIndexingFailure::query()->latest('failed_at')->limit(10)->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'search_provider' => 'required|string|in:' . implode(',', array_keys((array) config('search.providers', []))),
            'search_index_name' => 'required|string|max:100',
        ]);

        foreach (['search_provider', 'search_index_name'] as $key) {
            BusinessSetting::updateOrCreate(['type' => $key], ['value' => $request->input($key)]);
        }

        Cache::forget('business_settings');

        flash(translate('Search settings updated successfully'))->success();

        return back();
    }

    public function reindex(Request $request): RedirectResponse
    {
        $request->validate([
            'entity' => 'nullable|string|in:' . implode(',', SearchModelRegistry::entityOptions()),
            'id' => 'nullable|integer',
            'chunk' => 'nullable|integer|min:1|max:1000',
            'queue' => 'nullable|boolean',
            'dry_run' => 'nullable|boolean',
            'resume' => 'nullable|boolean',
            'run' => 'nullable|integer',
        ]);

        Artisan::call('search:reindex', [
            '--entity' => $request->input('entity', 'all'),
            '--id' => $request->filled('id') ? (int) $request->input('id') : null,
            '--chunk' => $request->input('chunk', 100),
            '--queue' => $request->boolean('queue'),
            '--dry-run' => $request->boolean('dry_run'),
            '--resume' => $request->boolean('resume'),
            '--run' => $request->filled('run') ? (int) $request->input('run') : null,
        ]);

        flash(trim(Artisan::output()) ?: translate('Search reindex command executed successfully'))->success();

        return back();
    }

    public function retryFailures(Request $request): RedirectResponse
    {
        Artisan::call('search:retry-failed', [
            '--queue' => $request->boolean('queue', true),
            '--run' => $request->filled('run') ? (int) $request->input('run') : null,
        ]);

        flash(trim(Artisan::output()) ?: translate('Search failures queued for retry'))->success();

        return back();
    }
}
