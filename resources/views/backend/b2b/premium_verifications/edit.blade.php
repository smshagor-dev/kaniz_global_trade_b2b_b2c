@extends('backend.layouts.app')

@section('content')
    <div class="col-lg-10 mx-auto">
        <div class="aiz-titlebar mt-2 mb-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="h3">{{ translate('Edit Premium Verification Package') }}</h3>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.b2b.premium-verifications.update', $package->id) }}" method="POST">
            @csrf
            @include('backend.b2b.premium_verifications._form', ['package' => $package, 'buttonText' => translate('Update Package')])
        </form>
    </div>
@endsection
