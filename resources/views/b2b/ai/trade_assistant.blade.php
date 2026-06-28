@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('Trade Assistant') }}</h1>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('b2b.ai.trade-assistant.ask') }}">
                @csrf
                <div class="form-group">
                    <label>{{ translate('Question') }}</label>
                    <textarea class="form-control rounded-0" name="question" rows="5" required>{{ old('question') }}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>{{ translate('Context Type') }}</label>
                        <input type="text" class="form-control rounded-0" name="context_type" value="{{ old('context_type') }}" placeholder="rfq, purchase-order, shipment">
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ translate('Context ID') }}</label>
                        <input type="number" class="form-control rounded-0" name="context_id" value="{{ old('context_id') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary rounded-0">{{ translate('Ask Assistant') }}</button>
            </form>
        </div>
    </div>

    @if ($answer)
        <div class="card rounded-0 shadow-none border">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ translate('Answer') }}</h5>
            </div>
            <div class="card-body">
                <div class="white-space-pre-line">{{ $answer['answer'] }}</div>
                <div class="text-muted small mt-3">{{ translate('Provider') }}: {{ $answer['provider'] }} Ã‚Â· {{ translate('Model') }}: {{ $answer['model'] }}</div>
            </div>
        </div>
    @endif
@endsection
