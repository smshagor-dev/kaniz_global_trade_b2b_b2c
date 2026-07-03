<?php

namespace App\Http\Controllers;

use App\Models\SearchDocument;
use App\Services\AI\GlobalSearchAIService;
use App\Services\Search\GlobalSearchService;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class EnterpriseSearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService,
        protected GlobalSearchService $globalSearchService,
        protected GlobalSearchAIService $globalSearchAIService
    )
    {
    }

    public function index(Request $request)
    {
        $query = (string) $request->get('q', '');
        $scope = $this->resolvedScope((string) $request->get('scope', 'ai_mode'));
        $results = null;
        $searchPayload = null;

        if ($query !== '') {
            $this->assertAiModeRateLimit($request, $scope);
            $country = (string) $request->input('country', '');
            $country = $country !== '' ? $country : (string) (selected_delivery_country_name() ?? '');
            $searchPayload = $this->globalSearchService->search($query, [
                'scope' => $scope,
                'include_private' => $request->boolean('include_private'),
                'country' => $country,
                'category_id' => (int) $request->input('category_id', 0),
                'limit' => 50,
            ], $request->user());
            $results = $searchPayload['results'];
            $this->globalSearchService->pushRecentSearch($query);
        }

        return view('frontend.enterprise_search.index', [
            'query' => $query,
            'results' => $results,
            'scope' => $scope,
            'searchPayload' => $searchPayload,
            'trendingKeywords' => $this->globalSearchService->trendingKeywords(),
            'recentSearches' => $this->globalSearchService->recentSearches(),
            'popularCategories' => $this->globalSearchService->popularCategories(),
            'countryOptions' => $this->globalSearchService->countries(),
        ]);
    }

    public function json(Request $request): JsonResponse
    {
        $query = (string) $request->get('q', '');
        $scope = $this->resolvedScope((string) $request->get('scope', 'ai_mode'));
        $this->assertAiModeRateLimit($request, $scope);
        $country = (string) $request->input('country', '');
        $country = $country !== '' ? $country : (string) (selected_delivery_country_name() ?? '');

        $payload = $this->globalSearchService->search($query, [
            'scope' => $scope,
            'include_private' => $request->boolean('include_private'),
            'country' => $country,
            'category_id' => (int) $request->input('category_id', 0),
            'limit' => (int) $request->input('limit', 20),
        ], $request->user());

        if ($scope === 'products' && trim($query) !== '' && !empty($payload['results']['results'])) {
            $normalizedQuery = mb_strtolower(trim($query));
            $filtered = collect((array) $payload['results']['results'])->filter(function (array $item) use ($normalizedQuery) {
                return mb_strtolower(trim((string) ($item['title'] ?? ''))) === $normalizedQuery;
            })->values();

            if ($filtered->isNotEmpty()) {
                $payload['results']['results'] = $filtered->all();
                $payload['results']['groups'] = $filtered->groupBy(fn ($item) => ucfirst(str_replace('_', ' ', (string) ($item['type'] ?? 'results'))))->map->values()->all();
                $payload['results']['total'] = $filtered->count();
            }
        }

        if ($query !== '') {
            $this->globalSearchService->pushRecentSearch($query);
        }

        return response()->json($payload);
    }

    public function suggestions(Request $request): JsonResponse
    {
        return response()->json(
            $this->globalSearchService->suggestions(
                (string) $request->get('q', ''),
                $this->resolvedScope((string) $request->get('scope', 'ai_mode')),
                $request->user()
            )
        );
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $scopeOptions = $this->globalSearchService->scopeOptions(
            $this->resolvedScope((string) $request->get('scope', 'ai_mode'))
        );

        return response()->json(
            $this->searchService->autocomplete((string) $request->get('q', ''), [
                'types' => $scopeOptions['types'],
                'filters' => $scopeOptions['filters'] ?? [],
                'include_private' => $request->boolean('include_private'),
                'limit' => (int) $request->input('limit', 8),
            ], $request->user())
        );
    }

    public function trending(): JsonResponse
    {
        return response()->json([
            'keywords' => $this->globalSearchService->trendingKeywords(),
        ]);
    }

    public function recent(): JsonResponse
    {
        return response()->json([
            'searches' => $this->globalSearchService->recentSearches(),
        ]);
    }

    public function imageSearch(Request $request): JsonResponse
    {
        if (get_setting('enable_global_search_image', '1') !== '1') {
            throw ValidationException::withMessages([
                'image' => [translate('Image search is disabled.')],
            ]);
        }

        $validated = $request->validate([
            'image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:' . (int) get_setting('global_search_image_max_upload_kb', 4096),
        ]);

        try {
            $analysis = $this->globalSearchAIService->analyzeImage($validated['image'], $request->user());
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
        $query = collect([
            $analysis['product_name'] ?? null,
            $analysis['category'] ?? null,
            implode(' ', array_slice((array) ($analysis['keywords'] ?? []), 0, 3)),
        ])->filter()->implode(' ');

        if ($query !== '') {
            $this->globalSearchService->pushRecentSearch($query);
        }

        return response()->json([
            'query' => trim($query),
            'analysis' => $analysis,
        ]);
    }

    public function click(Request $request, string $documentId): RedirectResponse
    {
        $document = SearchDocument::where('engine_document_id', $documentId)->firstOrFail();
        $this->searchService->recordClick($documentId, (string) $request->get('q', ''), $request->user());

        return redirect()->to($document->url ?: url('/global-search?q=' . urlencode((string) $request->get('q', ''))));
    }

    protected function resolvedScope(string $scope): string
    {
        return $this->globalSearchService->resolveScope($scope);
    }

    protected function assertAiModeRateLimit(Request $request, string $scope): void
    {
        if ($scope !== 'ai_mode') {
            return;
        }

        if (get_setting('enable_global_search_ai_mode', '1') !== '1') {
            return;
        }

        $key = 'global-search-ai-mode:' . ($request->user()?->id ?: $request->ip()) . ':' . now()->format('YmdHi');
        $count = (int) Cache::get($key, 0);

        if ($count >= 12) {
            throw ValidationException::withMessages([
                'q' => [translate('AI mode rate limit reached. Please wait a minute and try again.')],
            ]);
        }

        Cache::put($key, $count + 1, now()->addMinute());
    }
}
