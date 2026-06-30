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
use Throwable;

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
            'health' => $this->searchManager->healthReport($provider),
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
            'enable_global_search_header' => 'nullable|boolean',
            'enable_global_search_ai_mode' => 'nullable|boolean',
            'enable_global_search_image' => 'nullable|boolean',
            'global_search_image_max_upload_kb' => 'nullable|integer|min:512|max:10240',
            'global_search_trending_keywords' => 'nullable|string|max:2000',
            'global_search_popular_categories' => 'nullable|string|max:2000',
            'global_search_placeholder_text' => 'nullable|string|max:255',
            'search_opensearch_base_url' => 'nullable|url|max:255',
            'search_opensearch_username' => 'nullable|string|max:255',
            'search_opensearch_password' => 'nullable|string|max:255',
        ]);

        $values = [
            'search_provider' => $request->input('search_provider'),
            'search_index_name' => $request->input('search_index_name'),
            'enable_global_search_header' => $request->boolean('enable_global_search_header') ? '1' : '0',
            'enable_global_search_ai_mode' => $request->boolean('enable_global_search_ai_mode') ? '1' : '0',
            'enable_global_search_image' => $request->boolean('enable_global_search_image') ? '1' : '0',
            'global_search_image_max_upload_kb' => (string) $request->input('global_search_image_max_upload_kb', 4096),
            'global_search_trending_keywords' => (string) $request->input('global_search_trending_keywords', ''),
            'global_search_popular_categories' => (string) $request->input('global_search_popular_categories', ''),
            'global_search_placeholder_text' => (string) $request->input('global_search_placeholder_text', ''),
            'search_opensearch_base_url' => (string) $request->input('search_opensearch_base_url', ''),
            'search_opensearch_username' => (string) $request->input('search_opensearch_username', ''),
            'search_opensearch_password' => (string) $request->input('search_opensearch_password', ''),
        ];

        foreach ($values as $key => $value) {
            BusinessSetting::updateOrCreate(['type' => $key], ['value' => $value]);
        }

        Cache::forget('business_settings');

        flash(translate('Search settings updated successfully'))->success();

        return back();
    }

    public function manageIndex(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string|in:create,delete',
        ]);

        try {
            if ($request->input('action') === 'create') {
                $this->searchManager->createIndex();
                flash(translate('Search index created successfully'))->success();
            } else {
                $this->searchManager->deleteIndex();
                flash(translate('Search index deleted successfully'))->success();
            }
        } catch (Throwable $throwable) {
            flash($throwable->getMessage())->error();
        }

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
