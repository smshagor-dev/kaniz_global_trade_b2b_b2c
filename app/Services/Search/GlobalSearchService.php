<?php

namespace App\Services\Search;

use App\Models\Country;
use App\Models\SearchAnalyticsEvent;
use App\Models\User;
use App\Services\AI\GlobalSearchAIService;
use Illuminate\Support\Facades\Schema;

class GlobalSearchService
{
    public const SCOPES = [
        'ai_mode',
        'products',
        'manufacturers',
        'suppliers',
        'worldwide',
    ];

    public function __construct(
        protected SearchService $searchService,
        protected GlobalSearchAIService $aiService
    ) {
    }

    public function search(string $query, array $input = [], ?User $user = null): array
    {
        $scope = $this->normalizeScope((string) ($input['scope'] ?? 'ai_mode'));
        $includePrivate = (bool) ($input['include_private'] ?? false);
        $selectedCountry = trim((string) ($input['country'] ?? ''));
        $selectedCategory = (int) ($input['category_id'] ?? 0);
        $scopeOptions = $this->scopeOptions($scope);
        $aiInsights = null;

        if ($scope === 'ai_mode') {
            $aiInsights = $this->aiService->analyzeIntent($query, $user);
            if (!empty($aiInsights['search_types'])) {
                $scopeOptions['types'] = $aiInsights['search_types'];
            }

            if (!empty($aiInsights['search_filters'])) {
                $scopeOptions['filters'] = array_merge($scopeOptions['filters'], $aiInsights['search_filters']);
            }
        }

        $filters = array_merge($scopeOptions['filters'], array_filter([
            'country' => $selectedCountry !== '' ? $selectedCountry : null,
            'category_id' => $selectedCategory > 0 ? $selectedCategory : null,
        ], fn ($value) => $value !== null && $value !== ''));

        $results = $this->searchService->search($query, [
            'types' => $scopeOptions['types'],
            'filters' => $filters,
            'include_private' => $includePrivate,
            'limit' => (int) ($input['limit'] ?? 50),
            'metadata' => array_filter([
                'selected_tab' => $scope,
                'country' => $selectedCountry,
                'category_id' => $selectedCategory > 0 ? $selectedCategory : null,
                'ai_mode' => $scope === 'ai_mode',
                'company_ids' => $user ? $this->companyIdsForUser($user) : [],
                'ai_summary' => $aiInsights['summary'] ?? null,
            ], fn ($value) => $value !== null),
        ], $user);

        if ($scope === 'ai_mode') {
            $this->searchService->recordCustomEvent('ai_mode', $query, [
                'provider' => $results['provider'] ?? null,
                'result_count' => $results['total'] ?? 0,
                'metadata' => [
                    'selected_tab' => $scope,
                    'ai_available' => $aiInsights['ai_available'] ?? false,
                    'suggested_actions' => $aiInsights['suggested_actions'] ?? [],
                ],
            ], $user);

        if ($scope === 'products' && trim($query) !== '') {
            $results = $this->refineProductResults($results, $query);
        }
        }

        return [
            'scope' => $scope,
            'query' => $query,
            'results' => $results,
            'ai' => $aiInsights,
            'filters' => $filters,
        ];
    }

    public function suggestions(string $query, string $scope = 'ai_mode', ?User $user = null): array
    {
        $autocomplete = ['suggestions' => []];
        if (mb_strlen(trim($query)) >= 2) {
            $autocomplete = $this->searchService->autocomplete($query, [
                'types' => $this->scopeOptions($scope)['types'],
                'limit' => 8,
            ], $user);
        }

        return [
            'query' => $query,
            'suggestions' => $autocomplete['suggestions'],
            'recent' => $this->recentSearches(),
            'trending' => $this->trendingKeywords(),
        ];
    }

    public function trendingKeywords(): array
    {
        $configured = collect(explode(',', (string) get_setting('global_search_trending_keywords', '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        if ($configured !== []) {
            return $configured;
        }

        return SearchAnalyticsEvent::query()
            ->where('event_type', 'search')
            ->whereNotNull('query')
            ->groupBy('query')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(8)
            ->pluck('query')
            ->filter()
            ->values()
            ->all();
    }

    public function recentSearches(): array
    {
        return array_values(array_filter((array) session('global_recent_searches', [])));
    }

    public function pushRecentSearch(string $query): void
    {
        $query = trim($query);
        if ($query === '') {
            return;
        }

        $recent = collect($this->recentSearches())
            ->reject(fn ($item) => mb_strtolower($item) === mb_strtolower($query))
            ->prepend($query)
            ->take(8)
            ->values()
            ->all();

        session(['global_recent_searches' => $recent]);
    }

    public function scopeOptions(string $scope): array
    {
        return match ($this->normalizeScope($scope)) {
            'products' => [
                'types' => ['product', 'wholesale_product'],
                'filters' => [],
            ],
            'manufacturers' => [
                'types' => ['company'],
                'filters' => ['company_type' => 'manufacturer'],
            ],
            'suppliers' => [
                'types' => ['company'],
                'filters' => ['company_type' => 'supplier'],
            ],
            'worldwide' => [
                'types' => ['company', 'country', 'city', 'port', 'freight_forwarder', 'hs_code', 'shipment', 'container_shipment'],
                'filters' => [],
            ],
            default => [
                'types' => [],
                'filters' => [],
            ],
        };
    }

    public function popularCategories(): array
    {
        return collect(get_level_zero_categories())
            ->take(8)
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values()
            ->all();
    }

    public function countries(): array
    {
        if (!Schema::hasTable('countries')) {
            return [];
        }

        return Country::query()
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name', 'code'])
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
            ])
            ->values()
            ->all();
    }

    protected function normalizeScope(string $scope): string
    {
        return in_array($scope, self::SCOPES, true) ? $scope : 'ai_mode';
    }

    protected function companyIdsForUser(User $user): array
    {
        if (!Schema::hasTable('b2b_companies')) {
            return [];
        }

        return \App\Models\B2BCompany::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->merge(
                \DB::table('b2b_company_members')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->pluck('b2b_company_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function refineProductResults(array $results, string $query): array
    {
        $normalizedQuery = mb_strtolower(trim($query));
        $terms = array_values(array_filter(preg_split('/\s+/', $normalizedQuery) ?: []));

        $filtered = collect((array) ($results['results'] ?? []));

        $exact = $filtered->filter(function (array $item) use ($normalizedQuery) {
            return mb_strtolower(trim((string) ($item['title'] ?? ''))) === $normalizedQuery;
        })->values();

        if ($exact->isNotEmpty()) {
            $filtered = $exact;
        } else {
            $containsAllTerms = $filtered->filter(function (array $item) use ($terms) {
                $title = mb_strtolower((string) ($item['title'] ?? ''));
                foreach ($terms as $term) {
                    if (!str_contains($title, $term)) {
                        return false;
                    }
                }

                return true;
            })->values();

            if ($containsAllTerms->isNotEmpty()) {
                $filtered = $containsAllTerms;
            }
        }

        $results['results'] = $filtered->all();
        $results['groups'] = $filtered->groupBy(fn ($item) => ucfirst(str_replace('_', ' ', (string) ($item['type'] ?? 'results'))))->map->values()->all();
        $results['total'] = $filtered->count();

        return $results;
    }
}
