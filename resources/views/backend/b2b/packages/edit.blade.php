@extends('backend.layouts.app')

@section('content')
    <div class="col-lg-10 mx-auto">
        <div class="aiz-titlebar mt-2 mb-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="h3">
                        {{ !empty($forceSupplierFeaturedPackage) ? translate('Edit Supplier Featured Package') : translate('Edit B2B Package') }}
                    </h3>
                </div>
            </div>
        </div>

        <form action="{{ route($updateRoute ?? 'admin.b2b.packages.update', $package->id) }}" method="POST">
            @csrf
            @if (!empty($forceSupplierFeaturedPackage))
                <input type="hidden" name="force_supplier_featured_package" value="1">
            @endif
            @include('backend.b2b.packages._form', ['package' => $package, 'buttonText' => translate('Update Package')])
        </form>
    </div>
@endsection
