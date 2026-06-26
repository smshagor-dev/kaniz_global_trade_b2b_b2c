@extends('backend.layouts.app')

@section('content')
    <div class="col-lg-10 mx-auto">
        <div class="aiz-titlebar mt-2 mb-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="h3">
                        {{ !empty($forceSupplierFeaturedPackage) ? translate('Create Supplier Featured Package') : translate('Create B2B Package') }}
                    </h3>
                </div>
            </div>
        </div>

        <form action="{{ route($storeRoute ?? 'admin.b2b.packages.store') }}" method="POST">
            @csrf
            @if (!empty($forceSupplierFeaturedPackage))
                <input type="hidden" name="force_supplier_featured_package" value="1">
            @endif
            @include('backend.b2b.packages._form', ['buttonText' => translate('Save Package')])
        </form>
    </div>
@endsection
