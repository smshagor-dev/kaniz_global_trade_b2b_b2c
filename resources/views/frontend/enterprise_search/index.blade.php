@extends('frontend.layouts.app')

@section('meta_title', translate('Global Marketplace Search'))

@section('content')
<section class="py-4 py-lg-5 bg-light">
    <div class="container">
        <div class="bg-white border p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-3">
                <div>
                    <h1 class="fs-24 fw-700 mb-2">{{ translate('Global Marketplace Search') }}</h1>
                    <p class="text-muted mb-0">{{ translate('Search products, suppliers, companies, RFQs, logistics records, HS codes, ports and more without replacing the existing store search.') }}</p>
                </div>
                <a href="{{ route('search') }}" class="btn btn-soft-primary mt-3 mt-lg-0">{{ translate('Back to Existing Search') }}</a>
            </div>

            <form method="GET" action="{{ route('global.search') }}">
                <input type="hidden" name="scope" value="{{ $scope ?? 'ai_mode' }}">
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" name="q" id="enterprise-search-input" value="{{ $query }}" placeholder="{{ translate('Search product, supplier, company, RFQ, tracking number, HS code, port...') }}" autocomplete="off">
                    <div class="input-group-append"><button type="submit" class="btn btn-primary px-4">{{ translate('Search') }}</button></div>
                </div>
                @auth
                    <div class="mt-3">
                        <label class="aiz-switch aiz-switch-success mb-0"><input type="checkbox" name="include_private" value="1" {{ request()->boolean('include_private') ? 'checked' : '' }}><span></span></label>
                        <span class="ml-2">{{ translate('Include permission-aware internal records') }}</span>
                    </div>
                @endauth
                <div id="enterprise-search-suggestions" class="list-group position-relative mt-2"></div>
            </form>
        </div>
    </div>
</section>

@if ($results)
<section class="py-4 py-lg-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fs-20 fw-700 mb-0">{{ translate('Results') }}</h2>
            <span class="text-muted">{{ $results['total'] }} {{ translate('matches') }} | {{ strtoupper($results['provider']) }}</span>
        </div>

        @forelse ($results['groups'] as $group => $items)
            <div class="bg-white border mb-4">
                <div class="border-bottom px-4 py-3"><h3 class="fs-18 fw-700 mb-0">{{ $group }}</h3></div>
                <div class="p-4">
                    @foreach ($items as $item)
                        <div class="pb-3 {{ !$loop->last ? 'mb-3 border-bottom' : '' }}">
                            <div class="d-flex justify-content-between flex-column flex-lg-row">
                                <div>
                                    @if (!empty($item['url']))
                                        <a href="{{ route('global.search.click', ['documentId' => $item['engine_document_id'], 'q' => $query]) }}" class="text-reset"><h4 class="fs-16 fw-700 mb-1">{{ $item['title'] }}</h4></a>
                                    @else
                                        <h4 class="fs-16 fw-700 mb-1">{{ $item['title'] }}</h4>
                                    @endif
                                    @if (!empty($item['subtitle']))
                                        <div class="text-primary fs-13 mb-2">{{ $item['subtitle'] }}</div>
                                    @endif
                                    @if (!empty($item['summary']))
                                        <p class="text-muted mb-2">{{ $item['summary'] }}</p>
                                    @endif
                                </div>
                                <div class="text-muted fs-12">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $item['type'])) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white border p-4 text-center text-muted">{{ translate('No results found.') }}</div>
        @endforelse
    </div>
</section>
@endif
@endsection

@section('script')
<script>
    (function () {
        var input = document.getElementById('enterprise-search-input');
        var container = document.getElementById('enterprise-search-suggestions');
        var timeout;
        if (!input || !container) return;

        input.addEventListener('input', function () {
            clearTimeout(timeout);
            var value = input.value.trim();
            var scope = @json($scope ?? 'ai_mode');
            if (value.length < 2) {
                container.innerHTML = '';
                return;
            }

            timeout = setTimeout(function () {
                fetch('{{ route('global.search.autocomplete') }}?q=' + encodeURIComponent(value) + '&scope=' + encodeURIComponent(scope))
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        container.innerHTML = '';
                        (data.suggestions || []).forEach(function (suggestion) {
                            var link = document.createElement('a');
                            link.className = 'list-group-item list-group-item-action';
                            link.href = suggestion.url || ('{{ route('global.search') }}?q=' + encodeURIComponent(suggestion.title) + '&scope=' + encodeURIComponent(scope));
                            link.innerHTML = '<strong>' + suggestion.title + '</strong>' + (suggestion.subtitle ? '<div class="text-muted fs-12">' + suggestion.subtitle + '</div>' : '');
                            container.appendChild(link);
                        });
                    });
            }, 200);
        });
    })();
</script>
@endsection
