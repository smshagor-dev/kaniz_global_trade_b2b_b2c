@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Edit RFQ') }}</h1>
            </div>
        </div>
    </div>

    @include('b2b.rfqs._form', [
        'title' => translate('Update RFQ'),
        'action' => route('b2b.rfqs.update', $rfq->id),
        'submitText' => translate('Update RFQ'),
    ])
@endsection
