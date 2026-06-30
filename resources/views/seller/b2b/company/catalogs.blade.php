@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Supplier Catalogs') }}</h1>
            </div>
        </div>
    </div>

    @include('seller.b2b.company.partials.catalog_manager', ['catalogs' => $catalogs])
@endsection

@section('script')
    @stack('catalog_scripts')
@endsection
