<div class="card rounded-0 shadow-none border mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ translate('Financial Operations') }}</span>
        <span class="badge badge-soft-primary">{{ translate('Escrow, refunds, disputes, settlements') }}</span>
    </div>
    <div class="card-body">
        <div class="row gutters-12 mb-3">
            <div class="col-md-3">
                <div class="border p-3 h-100">
                    <div class="fs-12 text-muted">{{ translate('Escrows') }}</div>
                    <div class="fs-24 fw-700">{{ $invoice->escrows->count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border p-3 h-100">
                    <div class="fs-12 text-muted">{{ translate('Milestones') }}</div>
                    <div class="fs-24 fw-700">{{ $invoice->milestones->count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border p-3 h-100">
                    <div class="fs-12 text-muted">{{ translate('Refunds') }}</div>
                    <div class="fs-24 fw-700">{{ $invoice->financeRefunds->count() }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border p-3 h-100">
                    <div class="fs-12 text-muted">{{ translate('Disputes') }}</div>
                    <div class="fs-24 fw-700">{{ $invoice->financeDisputes->count() }}</div>
                </div>
            </div>
        </div>

        @if($invoice->escrows->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Escrow') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Settlement') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->escrows as $escrow)
                            <tr>
                                <td>#{{ $escrow->id }}</td>
                                <td><span class="badge badge-inline badge-info">{{ ucfirst($escrow->status) }}</span></td>
                                <td>{{ $escrow->amount }} {{ $escrow->currency }}</td>
                                <td>
                                    @if(Route::currentRouteName() === 'seller.b2b.proforma-invoices.show' && $escrow->status === 'released')
                                        <form action="{{ route('seller.b2b.trade-finance.settlements.store', $escrow->id) }}" method="POST">
                                            @csrf
                                            <div class="input-group input-group-sm">
                                                <select name="settlement_method" class="form-control">
                                                    <option value="wallet">Wallet</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                    <option value="wise">Wise</option>
                                                    <option value="payoneer">Payoneer</option>
                                                    <option value="manual">Manual</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="submit">{{ translate('Request') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    @else
                                        <span class="text-muted">{{ $escrow->settlements->last()->status ?? translate('Not requested') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="row gutters-12">
            <div class="col-lg-6">
                <form action="{{ (Route::currentRouteName() === 'seller.b2b.proforma-invoices.show') ? route('seller.b2b.trade-finance.disputes.store', $invoice->id) : route('b2b.trade-finance.disputes.store', $invoice->id) }}" method="POST" class="border p-3 mb-3">
                    @csrf
                    <h5 class="fs-14 mb-3">{{ translate('Raise Finance Dispute') }}</h5>
                    <div class="form-group">
                        <select name="category" class="form-control" required>
                            <option value="late_shipment">{{ translate('Late Shipment') }}</option>
                            <option value="wrong_product">{{ translate('Wrong Product') }}</option>
                            <option value="damage">{{ translate('Damage') }}</option>
                            <option value="payment">{{ translate('Payment') }}</option>
                            <option value="document_issue">{{ translate('Document Issue') }}</option>
                            <option value="refund">{{ translate('Refund') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="text" name="title" class="form-control" placeholder="{{ translate('Dispute title') }}" required>
                    </div>
                    <div class="form-group">
                        <textarea name="description" class="form-control" rows="3" placeholder="{{ translate('Describe the issue') }}" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning rounded-0">{{ translate('Create Dispute') }}</button>
                </form>
            </div>
            <div class="col-lg-6">
                <form action="{{ (Route::currentRouteName() === 'seller.b2b.proforma-invoices.show') ? route('seller.b2b.trade-finance.refunds.store', $invoice->id) : route('b2b.trade-finance.refunds.store', $invoice->id) }}" method="POST" class="border p-3">
                    @csrf
                    <h5 class="fs-14 mb-3">{{ translate('Request Refund') }}</h5>
                    <div class="form-group">
                        <input type="number" step="0.01" min="0.01" max="{{ $invoice->buyer_payable_total ?: $invoice->grand_total }}" name="amount" class="form-control" placeholder="{{ translate('Amount') }}" required>
                    </div>
                    <div class="form-group">
                        <select name="refund_type" class="form-control" required>
                            <option value="full">{{ translate('Full Refund') }}</option>
                            <option value="partial">{{ translate('Partial Refund') }}</option>
                            <option value="escrow">{{ translate('Escrow Refund') }}</option>
                            <option value="gateway">{{ translate('Gateway Refund') }}</option>
                            <option value="manual">{{ translate('Manual Refund') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <textarea name="reason" class="form-control" rows="3" placeholder="{{ translate('Refund reason') }}" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger rounded-0">{{ translate('Submit Refund Request') }}</button>
                </form>
            </div>
        </div>

        @if($invoice->financeDisputes->isNotEmpty())
            <div class="mt-3">
                <h5 class="fs-14">{{ translate('Dispute Timeline') }}</h5>
                @foreach($invoice->financeDisputes as $dispute)
                    <div class="border p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $dispute->title }}</strong>
                                <div class="text-muted fs-12">{{ ucfirst(str_replace('_', ' ', $dispute->category)) }}</div>
                            </div>
                            <span class="badge badge-inline badge-secondary">{{ ucfirst($dispute->status) }}</span>
                        </div>
                        <div class="mt-2">{{ $dispute->description }}</div>
                        @if($dispute->messages->isNotEmpty())
                            <div class="mt-2 pt-2 border-top">
                                @foreach($dispute->messages as $message)
                                    <div class="mb-2">
                                        <div class="fs-12 text-muted">{{ translate('Message') }} | {{ $message->created_at->format('d M, Y H:i') }}</div>
                                        <div>{{ $message->message }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <form action="{{ (Route::currentRouteName() === 'seller.b2b.proforma-invoices.show') ? route('seller.b2b.trade-finance.disputes.messages.store', $dispute->id) : route('b2b.trade-finance.disputes.messages.store', $dispute->id) }}" method="POST" class="mt-2">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="{{ translate('Add dispute message') }}" required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="submit">{{ translate('Send') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
