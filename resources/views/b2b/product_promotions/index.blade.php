@extends('b2b.layouts.app')

@section('panel_content')
    <div class="card overflow-hidden mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #3f2b96, #6b4ce6 55%, #9a7cff);">
        <div class="card-body p-4 p-lg-5 text-white">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge badge-inline badge-warning mb-3">{{ translate('SPONSORED PRODUCT SYSTEM') }}</span>
                    <h2 class="fw-700 mb-3">{{ translate('Sponsored Product Packages') }}</h2>
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

                        @if ($package->description)
                            <div class="text-muted mb-3">{{ $package->description }}</div>
                        @endif

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
                            <button type="button" class="btn btn-primary btn-block" onclick="openSponsoredProductPurchaseModal({{ $package->id }})">{{ translate('Purchase Sponsored Product Package') }}</button>
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

@section('modal')
    <div class="modal fade" id="sponsored_product_payment_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Purchase Sponsored Product Package') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sponsored_product_payment_form" method="POST">
                    @csrf
                    <div class="modal-body" style="overflow-y: unset;">
                        <div class="row">
                            <div class="col-md-3">
                                <label>{{ translate('Payment Method') }}</label>
                            </div>
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <select class="form-control aiz-selectpicker" data-live-search="true" name="payment_option" required>
                                        @include('partials.online_payment_options')
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-right mb-0">
                            <button type="button" class="btn btn-sm btn-secondary mr-1" data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Confirm Payment') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function openSponsoredProductPurchaseModal(id) {
            var actionTemplate = @json(url('/b2b/sponsored-products/packages/__ID__/purchase'));
            document.getElementById('sponsored_product_payment_form').setAttribute('action', actionTemplate.replace('__ID__', id));
            $('#sponsored_product_payment_modal').modal('show');
        }
    </script>
@endsection
