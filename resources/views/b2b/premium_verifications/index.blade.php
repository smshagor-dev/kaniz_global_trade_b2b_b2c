@extends('b2b.layouts.app')

@section('panel_content')
    <div class="card overflow-hidden mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #0b3d2e, #146c43 55%, #1f8f57);">
        <div class="card-body p-4 p-lg-5 text-white">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge badge-inline badge-warning mb-3">{{ translate('PREMIUM VERIFICATION') }}</span>
                    <h2 class="fw-700 mb-3">{{ translate('Company Premium Verification') }}</h2>
                    <p class="opacity-80 mb-0">{{ translate('This is a fully separate paid verification flow for companies. It does not replace your membership, featured supplier package, or sponsored product package.') }}</p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="bg-white text-dark rounded p-4">
                        <div class="text-muted fs-12">{{ translate('Current Premium Verification') }}</div>
                        <div class="fw-700 fs-18 mb-2">{{ $currentPackage?->name ?: translate('No active premium verification') }}</div>
                        <div class="fs-13">{{ translate('Company') }}: {{ $company->company_name }}</div>
                        <div class="fs-13">{{ translate('Premium Status') }}: {{ $company->premium_verified ? translate('Verified') : translate('Not verified') }}</div>
                        <div class="fs-13 mt-2">{{ translate('Verified At') }}: {{ $company->premium_verified_at ? $company->premium_verified_at->format('d M Y') : translate('Not set') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">{{ translate('Revenue Projection') }}</h5>
            <div class="fs-16 text-muted">{{ translate('Premium Verification') }}: <strong>{{ single_price($projection['price'] ?? 0) }}</strong></div>
            <div class="fs-20 fw-700 mt-2">{{ $projection['company_count'] ?? 0 }} x {{ single_price($projection['price'] ?? 0) }} = {{ single_price($projection['projected_revenue'] ?? 0) }}</div>
        </div>
    </div>

    <div class="row gutters-16">
        @foreach ($packages as $package)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="fw-700 fs-18">{{ $package->name }}</div>
                                @if ($package->highlight_text)
                                    <small class="text-success fw-700">{{ $package->highlight_text }}</small>
                                @endif
                            </div>
                            @if ($currentPackage?->id === $package->id)
                                <span class="badge badge-inline badge-success">{{ translate('Current') }}</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-end mb-4">
                            <div class="fs-28 fw-700">{{ $package->amount > 0 ? single_price($package->amount) : translate('Free') }}</div>
                        </div>

                        <div class="text-muted mb-3">{{ $package->description ?: translate('Premium company verification package.') }}</div>

                        <ul class="list-unstyled fs-13 mb-4">
                            <li class="mb-2">{{ translate('One-time premium verification fee') }}</li>
                            <li class="mb-2">{{ translate('Premium verified company status') }}</li>
                            <li class="mb-2">{{ translate('Projection') }}: {{ number_format($projection['company_count'] ?? 0) }} {{ translate('approved companies') }} x {{ single_price($package->amount) }} = {{ single_price($package->amount * ($projection['company_count'] ?? 0)) }}</li>
                        </ul>

                        @if ($currentPackage?->id === $package->id)
                            <button class="btn btn-soft-success btn-block" disabled>{{ translate('Active Premium Verification') }}</button>
                        @elseif ($package->amount == 0)
                            <form action="{{ route('b2b.premium-verifications.activate-free', $package->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">{{ translate('Activate Premium Verification') }}</button>
                            </form>
                        @else
                            <form action="{{ route('b2b.premium-verifications.request', $package->id) }}" method="POST">
                                @csrf
                                <input type="text" class="form-control mb-3" name="payment_reference" placeholder="{{ translate('Payment reference or transaction ID') }}" required>
                                <textarea class="form-control mb-3" name="payment_notes" rows="2" placeholder="{{ translate('Optional payment note or proof details') }}"></textarea>
                                <textarea class="form-control mb-3" name="note" rows="3" placeholder="{{ translate('Optional note for admin approval') }}"></textarea>
                                <button type="submit" class="btn btn-success btn-block">{{ translate('Request Premium Verification') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Premium Verification Request History') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Package') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Payment Reference') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Requested At') }}</th>
                            <th>{{ translate('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>{{ $request->package?->name ?: '-' }}</td>
                                <td>{{ single_price($request->amount) }}</td>
                                <td>{{ $request->payment_reference ?: '-' }}</td>
                                <td>{{ ucfirst($request->status) }}</td>
                                <td>{{ $request->requested_at ? $request->requested_at->format('d M Y h:i A') : '-' }}</td>
                                <td>{{ $request->note ?: ($request->rejection_note ?: '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">{{ translate('No premium verification requests found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
