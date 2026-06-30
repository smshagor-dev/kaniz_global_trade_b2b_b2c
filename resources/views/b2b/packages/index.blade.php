@extends('b2b.layouts.app')

@section('panel_content')
    <div class="card overflow-hidden mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #0f172a, #1e293b 55%, #334155);">
        <div class="card-body p-4 p-lg-5 text-white">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge badge-inline badge-warning mb-3">{{ strtoupper($packageFor) }} {{ translate('PLAN') }}</span>
                    <h2 class="fw-700 mb-3">{{ translate('B2B Membership Packages') }}</h2>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="bg-white text-dark rounded p-4">
                        <div class="text-muted fs-12">{{ translate('Current Membership Package') }}</div>
                        <div class="fw-700 fs-18 mb-2">{{ $currentPackage?->name ?: translate('No active package') }}</div>
                        <div class="fs-13">{{ translate('Company') }}: {{ $company->company_name }}</div>
                        <div class="fs-13">{{ translate('Membership Expires') }}: {{ $company->package_expires_at ? $company->package_expires_at->format('d M Y') : translate('Not set') }}</div>
                        @if ($packageFor === 'supplier')
                            <div class="fs-13 mt-2">{{ translate('Featured Package') }}: {{ $currentFeaturedPackage?->name ?: translate('No active featured package') }}</div>
                            <div class="fs-13">{{ translate('Featured Expires') }}: {{ $company->featured_package_expires_at ? $company->featured_package_expires_at->format('d M Y') : translate('Not set') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($packageFor === 'supplier' && count($featuredPackages) > 0)
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #fff8e1, #ffffff);">
            <div class="card-body p-4">
                <div class="mb-4">
                    <span class="badge badge-inline badge-warning mb-3">{{ translate('Supplier Featured Packages') }}</span>
                    <h3 class="fw-700 mb-2">{{ translate('Separate featured package system') }}</h3>
                </div>

                <div class="row gutters-16">
                    @foreach ($featuredPackages as $package)
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
                                        @if ($currentFeaturedPackage?->id === $package->id)
                                            <span class="badge badge-inline badge-success">{{ translate('Current Featured') }}</span>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-end mb-4">
                                        <div class="fs-28 fw-700">{{ $package->amount > 0 ? single_price($package->amount) : translate('Free') }}</div>
                                        <div class="text-muted ml-2">{{ $package->duration }} {{ translate('days') }}</div>
                                    </div>

                                    @if ($package->description)
                                        <div class="text-muted mb-3">{{ $package->description }}</div>
                                    @endif

                                    <ul class="list-unstyled fs-13 mb-4">
                                        <li class="mb-2">{{ translate('Homepage featured visibility included') }}</li>
                                        <li class="mb-2">{{ $package->priority_listing ? translate('Priority listing included') : translate('Standard listing') }}</li>
                                        <li class="mb-2">{{ $package->verified_badge ? translate('Verified badge included') : translate('No verified badge') }}</li>
                                        <li class="mb-2">{{ $package->analytics_access ? translate('Analytics access included') : translate('No advanced analytics') }}</li>
                                        <li class="mb-2">{{ $package->dedicated_support ? translate('Dedicated support') : translate('Standard support') }}</li>
                                        <li class="mb-2">{{ translate('Projection') }}: {{ number_format($featuredProjection['company_count'] ?? 0) }} {{ translate('companies') }} x {{ single_price($package->monthlyEquivalent()) }} = {{ single_price(($featuredProjection['company_count'] ?? 0) * $package->monthlyEquivalent()) }}/{{ translate('month') }}</li>
                                    </ul>

                                    @if ($currentFeaturedPackage?->id === $package->id)
                                        <button class="btn btn-soft-success btn-block" disabled>{{ translate('Active Featured Package') }}</button>
                                    @elseif ($package->amount == 0)
                                        <form action="{{ route('b2b.packages.activate-free', $package->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-block">{{ translate('Activate Featured Plan') }}</button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-warning btn-block" onclick="openB2BPackagePurchaseModal({{ $package->id }})">{{ translate('Purchase Featured Package') }}</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

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
                            <div class="text-muted ml-2">{{ $package->duration }} {{ translate('days') }}</div>
                        </div>

                        @if ($package->description)
                            <div class="text-muted mb-3">{{ $package->description }}</div>
                        @endif

                        <ul class="list-unstyled fs-13 mb-4">
                            <li class="mb-2">{{ translate('RFQs') }}: {{ $package->rfq_limit ?: translate('Unlimited') }}</li>
                            <li class="mb-2">{{ translate('Quotations') }}: {{ $package->quotation_limit ?: translate('Unlimited') }}</li>
                            <li class="mb-2">{{ translate('Wholesale Products') }}: {{ $package->product_limit ?: translate('Unlimited') }}</li>
                            <li class="mb-2">{{ translate('Team Members') }}: {{ $package->member_limit }}</li>
                            <li class="mb-2">{{ $package->priority_listing ? translate('Priority listing included') : translate('Standard listing') }}</li>
                            <li class="mb-2">{{ translate('Company membership package only') }}</li>
                            <li class="mb-2">{{ $package->analytics_access ? translate('Analytics access included') : translate('No advanced analytics') }}</li>
                            <li class="mb-2">{{ $package->dedicated_support ? translate('Dedicated support') : translate('Standard support') }}</li>
                        </ul>

                        @if ($currentPackage?->id === $package->id)
                            <button class="btn btn-soft-success btn-block" disabled>{{ translate('Active Package') }}</button>
                        @elseif ($package->amount == 0)
                            <form action="{{ route('b2b.packages.activate-free', $package->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block">{{ translate('Activate Free Plan') }}</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-primary btn-block" onclick="openB2BPackagePurchaseModal({{ $package->id }})">{{ translate('Purchase This Membership Package') }}</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Membership Package Request History') }}</h5>
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
                                <td colspan="6" class="text-center">{{ translate('No package requests found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($packageFor === 'supplier')
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">{{ translate('Featured Package Request History') }}</h5>
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
                            @forelse ($featuredRequests as $request)
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
                                    <td colspan="6" class="text-center">{{ translate('No featured package requests found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('modal')
    <div class="modal fade" id="b2b_package_payment_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Purchase Your Package') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="b2b_package_payment_form" method="POST">
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
        function openB2BPackagePurchaseModal(id) {
            var actionTemplate = @json(url('/b2b/packages/__ID__/purchase'));
            document.getElementById('b2b_package_payment_form').setAttribute('action', actionTemplate.replace('__ID__', id));
            $('#b2b_package_payment_modal').modal('show');
        }
    </script>
@endsection
