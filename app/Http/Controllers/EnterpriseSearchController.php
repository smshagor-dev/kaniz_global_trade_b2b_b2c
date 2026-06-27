<?php

namespace App\Http\Controllers;

use App\Models\SearchDocument;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnterpriseSearchController extends Controller
{
    public function __construct(protected SearchService $searchService)
    {
    }

    public function index(Request $request)
    {
        $query = (string) $request->get('q', '');
        $results = null;

        if ($query !== '') {
            $results = $this->searchService->search($query, [
                'types' => (array) $request->input('types', []),
                'filters' => array_filter((array) $request->input('filters', []), fn ($value) => $value !== null && $value !== ''),
                'include_private' => $request->boolean('include_private'),
                'limit' => 50,
            ], $request->user());
        }

        return view('frontend.enterprise_search.index', compact('query', 'results'));
    }

    public function json(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->search((string) $request->get('q', ''), [
                'types' => (array) $request->input('types', []),
                'filters' => array_filter((array) $request->input('filters', []), fn ($value) => $value !== null && $value !== ''),
                'include_private' => $request->boolean('include_private'),
                'limit' => (int) $request->input('limit', 20),
            ], $request->user())
        );
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->autocomplete((string) $request->get('q', ''), [
                'types' => (array) $request->input('types', []),
                'include_private' => $request->boolean('include_private'),
                'limit' => (int) $request->input('limit', 8),
            ], $request->user())
        );
    }

    public function click(Request $request, string $documentId): RedirectResponse
    {
        $document = SearchDocument::where('engine_document_id', $documentId)->firstOrFail();
        $this->searchService->recordClick($documentId, (string) $request->get('q', ''), $request->user());

        return redirect()->to($document->url ?: url('/global-search?q=' . urlencode((string) $request->get('q', ''))));
    }
}
