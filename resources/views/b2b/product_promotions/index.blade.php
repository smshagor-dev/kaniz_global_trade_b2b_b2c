@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card overflow-hidden mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #3f2b96, #6b4ce6 55%, #9a7cff);">
        <div class="card-body p-4 p-lg-5 text-white">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge badge-inline badge-warning mb-3">{{ translate('SPONSORED PRODUCT SYSTEM') }}</span>
                    <h2 class="fw-700 mb-3">{{ translate('Sponsored Product Packages') }}</h2>
                    <p class="opacity-80 mb-0">{{ translate('This is a completely separate paid flow for promoting wholesale products. It does not replace your company membership or featured supplier package.') }}</p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="bg-white text-dark rounded p-4">
                        <div class="text-muted fs-12">{{ translate('Current Sponsored Package') }}</div>
                        <div class="fw-700 fs-18 mb-2">{{ $currentPackage?->name ?: translate('No active sponsored package') }}</div>
                        <div class="fs-13">{{ translate('Company') }}: {{ $company->company_name }}</div>
                        <div class="fs-13">{{ translate('Expires') }}: {{ $company->product_promotion_expires_at ? $company->product_promotion_expires_at->format('d M Y') : translate('Not set') }}</div>
                        <div class="fs-13 mt-2">{{ translate('Remaining Promote Slots') }}: {{ is_null($remainingSlots) ? translate('Unlimited') : $remainingSlots }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">{{ translate('Revenue Projection') }}</h5>
            <div class="fs-16 text-muted">{{ $projection['plan_name'] ?: translate('Sponsored Product') }}: <strong>{{ single_price($projection['monthly_unit_price'] ?? 0) }}/{{ translate('month') }}</strong></div>
            <div class="fs-20 fw-700 mt-2">{{ $projection['product_count'] ?? 0 }} x {{ single_price($projection['monthly_unit_price'] ?? 0) }} = {{ single_price($projection['projected_monthly_revenue'] ?? 0) }}/{{ translate('month') }}</div>
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
                                    <small class="text-warning fw-700">{{ $package->highlight_text }}</small>
                                @endif
                            </div>
                            @if ($currentPackage?->id === $package->id)
                                <span class="badge badge-inline badge-success">{{ translate('Current') }}</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-end mb-4">
                            <div class="fs-28 fw-700">{{ $package->amount > 0 ? single_price($package->amount) : translate('Free') }}</div>
                            <div class="text-muted ml-2">{{ translate('per') }} {{ $package->duration }} {{ translate('days') }}</div>
                        </div>

                        <div class="text-muted mb-3">{{ $package->description ?: translate('Sponsored wholesale product promotion package.') }}</div>

                        <ul class="list-unstyled fs-13 mb-4">
                            <li class="mb-2">{{ number_format($package->product_limit) }} {{ translate('sponsored product slots') }}</li>
                            <li class="mb-2">{{ translate('Monthly Revenue Per Package') }}: {{ single_price($package->monthlyEquivalent()) }}</li>
                            <li class="mb-2">{{ translate('Per Product Rate') }}: {{ single_price(round($package->monthlyEquivalent() / max($package->product_limit, 1), 2)) }}/{{ translate('month') }}</li>
                        </ul>

                        @if ($currentPackage?->id === $package->id)
                            <button class="btn btn-soft-success btn-block" disabled>{{ translate('Active Sponsored Package') }}</button>
                        @elseif ($package->amount == 0)
                            <form action="{{ route('seller.b2b.product-promotions.activate-free', $package->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block">{{ translate('Activate Free Sponsored Plan') }}</button>
                            </form>
                        @else
                            <form action="{{ route('seller.b2b.product-promotions.request', $package->id) }}" method="POST">
                                @csrf
                                <input type="text" class="form-control mb-3" name="payment_reference" placeholder="{{ translate('Payment reference or transaction ID') }}" required>
                                <textarea class="form-control mb-3" name="payment_notes" rows="2" placeholder="{{ translate('Optional payment note or proof details') }}"></textarea>
                                <textarea class="form-control mb-3" name="note" rows="3" placeholder="{{ translate('Optional note for admin approval') }}"></textarea>
                                <button type="submit" class="btn btn-primary btn-block">{{ translate('Request Sponsored Product Package') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ translate('Active Sponsored Products') }}</h5>
            <a href="{{ route('seller.wholesale_products_list') }}" class="btn btn-soft-primary btn-sm">{{ translate('Manage Wholesale Products') }}</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Product') }}</th>
                            <th>{{ translate('Package') }}</th>
                            <th>{{ translate('Expires') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($promotedProducts as $promotion)
                            <tr>
                                <td>{{ $promotion->product?->getTranslation('name') ?: '-' }}</td>
                                <td>{{ $promotion->package?->name ?: ($currentPackage?->name ?: '-') }}</td>
                                <td>{{ $promotion->expires_at ? $promotion->expires_at->format('d M Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ translate('No active sponsored products found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Sponsored Package Request History') }}</h5>
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
                                <td colspan="6" class="text-center">{{ translate('No sponsored package requests found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
