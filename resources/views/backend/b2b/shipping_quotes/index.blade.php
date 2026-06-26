@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Shipping Quotes') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            @include('b2b.partials.shipping_quotes_table', ['quotes' => $shippingQuotes, 'allowSelect' => false])
            <div class="aiz-pagination mt-4">{{ $shippingQuotes->links() }}</div>
        </div>
    </div>
@endsection
