<div class="card rounded-0 shadow-none border mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ translate('Trade Finance Board') }}</span>
        <span class="badge badge-soft-info">{{ translate('Milestones, LC, disputes and payout readiness') }}</span>
    </div>
    <div class="card-body">
        <div class="row gutters-12 mb-3">
            <div class="col-md-4">
                <div class="border p-3 h-100">
                    <div class="fs-12 text-muted">{{ translate('Milestones') }}</div>
                    <div class="fs-24 fw-700">{{ $purchaseOrder->milestones->count() }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border p-3 h-100">
                    <div class="fs-12 text-muted">{{ translate('Letters Of Credit') }}</div>
                    <div class="fs-24 fw-700">{{ $purchaseOrder->lettersOfCredit->count() }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border p-3 h-100">
                    <div class="fs-12 text-muted">{{ translate('Open Disputes') }}</div>
                    <div class="fs-24 fw-700">{{ $purchaseOrder->financeDisputes->where('status', 'open')->count() }}</div>
                </div>
            </div>
        </div>

        @if($purchaseOrder->milestones->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Milestone') }}</th>
                            <th>{{ translate('Trigger') }}</th>
                            <th>{{ translate('Share') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->milestones as $milestone)
                            <tr>
                                <td>{{ $milestone->title }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $milestone->trigger_event)) }}</td>
                                <td>{{ $milestone->percentage }}%</td>
                                <td>{{ $milestone->amount }} {{ $milestone->currency }}</td>
                                <td><span class="badge badge-inline badge-secondary">{{ ucfirst($milestone->status) }}</span></td>
                                <td>
                                    @if(Route::currentRouteName() === 'b2b.purchase-orders.show' && in_array($milestone->status, ['pending'], true))
                                        <form action="{{ route('b2b.trade-finance.milestones.fund', $milestone->id) }}" method="POST">
                                            @csrf
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="reference" class="form-control" placeholder="{{ translate('Reference') }}" required>
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="submit">{{ translate('Fund') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    @elseif(Route::currentRouteName() === 'b2b.purchase-orders.show' && $milestone->status === 'funded')
                                        <form action="{{ route('b2b.trade-finance.milestones.release', $milestone->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-success btn-sm" type="submit">{{ translate('Release') }}</button>
                                        </form>
                                    @else
                                        <span class="text-muted">{{ translate('Tracked') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(Route::currentRouteName() === 'b2b.purchase-orders.show' && $purchaseOrder->status === 'accepted')
            <div class="row gutters-12">
                <div class="col-lg-7">
                    <form action="{{ route('b2b.trade-finance.milestones.store', $purchaseOrder->id) }}" method="POST" class="border p-3 mb-3">
                        @csrf
                        <h5 class="fs-14 mb-3">{{ translate('Configure Standard Milestones') }}</h5>
                        <input type="hidden" name="milestones[0][title]" value="Deposit">
                        <input type="hidden" name="milestones[0][trigger_event]" value="production">
                        <input type="hidden" name="milestones[1][title]" value="Shipment">
                        <input type="hidden" name="milestones[1][trigger_event]" value="shipment">
                        <input type="hidden" name="milestones[2][title]" value="Delivery">
                        <input type="hidden" name="milestones[2][trigger_event]" value="delivery">
                        <div class="form-group">
                            <label>{{ translate('Deposit Percentage') }}</label>
                            <input type="number" name="milestones[0][percentage]" step="0.01" min="0.01" max="100" class="form-control" value="30" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Shipment Percentage') }}</label>
                            <input type="number" name="milestones[1][percentage]" step="0.01" min="0.01" max="100" class="form-control" value="40" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Delivery Percentage') }}</label>
                            <input type="number" name="milestones[2][percentage]" step="0.01" min="0.01" max="100" class="form-control" value="30" required>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-0">{{ translate('Save Milestones') }}</button>
                    </form>
                </div>
                <div class="col-lg-5">
                    <form action="{{ route('b2b.trade-finance.letters-of-credit.store', $purchaseOrder->id) }}" method="POST" class="border p-3">
                        @csrf
                        <h5 class="fs-14 mb-3">{{ translate('Request Letter Of Credit') }}</h5>
                        <div class="form-group">
                            <input type="text" name="lc_number" class="form-control" placeholder="{{ translate('LC Number') }}" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="issuing_bank" class="form-control" placeholder="{{ translate('Issuing Bank') }}" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="advising_bank" class="form-control" placeholder="{{ translate('Advising Bank') }}">
                        </div>
                        <div class="form-group">
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="{{ translate('Amount') }}" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="currency" class="form-control" value="{{ $purchaseOrder->currency }}" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="required_documents" class="form-control" placeholder="{{ translate('Required documents comma separated') }}">
                        </div>
                        <button type="submit" class="btn btn-warning rounded-0">{{ translate('Create LC Workflow') }}</button>
                    </form>
                </div>
            </div>
        @endif

        @if($purchaseOrder->lettersOfCredit->isNotEmpty())
            <div class="mt-3">
                <h5 class="fs-14">{{ translate('LC Timeline') }}</h5>
                @foreach($purchaseOrder->lettersOfCredit as $lc)
                    <div class="border p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $lc->lc_number }}</strong>
                            <span class="badge badge-inline badge-info">{{ ucfirst(str_replace('_', ' ', $lc->status)) }}</span>
                        </div>
                        <div class="text-muted fs-12">{{ $lc->issuing_bank }} / {{ $lc->advising_bank ?: translate('No advising bank') }}</div>
                        <div class="fs-13 mt-1">{{ $lc->amount }} {{ $lc->currency }} | {{ translate('Expiry') }}: {{ optional($lc->expiry_date)->format('d M, Y') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
