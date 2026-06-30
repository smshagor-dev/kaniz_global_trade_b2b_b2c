@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('Supplier Trade Finance Dashboard') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} · {{ translate('Live settlement, escrow, LC, dispute and refund operations') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Pending Settlements') }}</div>
                    <div class="fs-28 fw-700">{{ $stats['finance_pending_settlements'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Completed Settlements') }}</div>
                    <div class="fs-28 fw-700">{{ $stats['finance_completed_settlements'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Open Disputes') }}</div>
                    <div class="fs-28 fw-700">{{ $stats['finance_open_disputes'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Released Escrow Value') }}</div>
                    <div class="fs-28 fw-700">{{ single_price($stats['finance_released_escrow_value'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Milestones Active') }}</div>
                    <div class="fs-28 fw-700">{{ $stats['finance_milestones_due'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Active LCs') }}</div>
                    <div class="fs-28 fw-700">{{ $stats['finance_active_lcs'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Refund Requests') }}</div>
                    <div class="fs-28 fw-700">{{ $stats['finance_refunds'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Finance Queue Items') }}</div>
                    <div class="fs-28 fw-700">{{ $purchaseOrders->total() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-xl-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ translate('Released Escrows Ready for Settlement') }}</h5>
                    <span class="badge badge-inline badge-soft-secondary">{{ $releasedEscrows->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('Escrow') }}</th>
                                    <th>{{ translate('Amount') }}</th>
                                    <th>{{ translate('Released') }}</th>
                                    <th>{{ translate('Settlement') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($releasedEscrows as $escrow)
                                    <tr>
                                        <td>
                                            <div class="fw-600">#{{ $escrow->id }}</div>
                                            <small class="text-muted">{{ class_basename($escrow->reference_type) }}</small>
                                        </td>
                                        <td>{{ number_format((float) ($escrow->released_amount ?: $escrow->amount), 2) }} {{ $escrow->currency }}</td>
                                        <td>{{ $escrow->released_at?->format('d M Y, h:i A') ?? '-' }}</td>
                                        <td>
                                            @if ($escrow->settlements->isNotEmpty())
                                                <span class="badge badge-inline badge-soft-info">{{ ucfirst(str_replace('_', ' ', $escrow->settlements->last()->status)) }}</span>
                                            @elseif ($canManageTradeFinance)
                                                <form action="{{ route('seller.b2b.trade-finance.settlements.store', $escrow->id) }}" method="POST" class="d-flex flex-column">
                                                    @csrf
                                                    <select name="settlement_method" class="form-control form-control-sm mb-2" required>
                                                        <option value="bank_transfer">{{ translate('Bank Transfer') }}</option>
                                                        <option value="wise">Wise</option>
                                                        <option value="payoneer">Payoneer</option>
                                                        <option value="wallet">{{ translate('Wallet') }}</option>
                                                        <option value="manual">{{ translate('Manual') }}</option>
                                                    </select>
                                                    <input type="text" name="reference" class="form-control form-control-sm mb-2" placeholder="{{ translate('Reference') }}">
                                                    <button type="submit" class="btn btn-primary btn-sm">{{ translate('Request') }}</button>
                                                </form>
                                            @else
                                                <span class="text-muted">{{ translate('No permission') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ translate('No released escrows available for settlement.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ translate('Recent Settlements') }}</h5>
                    <span class="badge badge-inline badge-soft-secondary">{{ $recentSettlements->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('Settlement') }}</th>
                                    <th>{{ translate('Method') }}</th>
                                    <th>{{ translate('Net Amount') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSettlements as $settlement)
                                    <tr>
                                        <td>
                                            <div class="fw-600">#{{ $settlement->id }}</div>
                                            <small class="text-muted">{{ $settlement->requested_at?->format('d M Y, h:i A') ?? '-' }}</small>
                                        </td>
                                        <td>{{ ucwords(str_replace('_', ' ', $settlement->settlement_method)) }}</td>
                                        <td>{{ number_format((float) $settlement->net_amount, 2) }} {{ $settlement->currency }}</td>
                                        <td><span class="badge badge-inline badge-soft-success">{{ ucfirst(str_replace('_', ' ', $settlement->status)) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ translate('No settlement requests found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-xl-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ translate('Letter of Credit Pipeline') }}</h5>
                    <span class="badge badge-inline badge-soft-secondary">{{ $lettersOfCredit->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('LC') }}</th>
                                    <th>{{ translate('Bank') }}</th>
                                    <th>{{ translate('Amount') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lettersOfCredit as $lc)
                                    <tr>
                                        <td>
                                            <div class="fw-600">{{ $lc->lc_number }}</div>
                                            <small class="text-muted">{{ $lc->expiry_date?->format('d M Y') ?? '-' }}</small>
                                        </td>
                                        <td>{{ $lc->issuing_bank }}</td>
                                        <td>{{ number_format((float) $lc->amount, 2) }} {{ $lc->currency }}</td>
                                        <td>
                                            @if ($canManageTradeFinance)
                                                <form action="{{ route('seller.b2b.trade-finance.letters-of-credit.status', $lc->id) }}" method="POST" class="d-flex flex-column">
                                                    @csrf
                                                    <select name="status" class="form-control form-control-sm mb-2" required>
                                                        @foreach (['bank_review', 'approved', 'documents_uploaded', 'shipment', 'lc_released', 'completed', 'rejected'] as $status)
                                                            <option value="{{ $status }}" @selected($lc->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-soft-primary btn-sm">{{ translate('Update') }}</button>
                                                </form>
                                            @else
                                                <span class="badge badge-inline badge-soft-info">{{ ucfirst(str_replace('_', ' ', $lc->status)) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ translate('No letters of credit found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ translate('Open Disputes') }}</h5>
                    <span class="badge badge-inline badge-soft-secondary">{{ $openDisputes->count() }}</span>
                </div>
                <div class="card-body">
                    @forelse ($openDisputes as $dispute)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-600">{{ $dispute->title }}</div>
                                    <div class="text-muted fs-12">{{ ucfirst(str_replace('_', ' ', $dispute->category)) }}</div>
                                </div>
                                <span class="badge badge-inline badge-soft-warning">{{ ucfirst($dispute->status) }}</span>
                            </div>
                            <div class="mt-2">{{ $dispute->description }}</div>
                            <div class="mt-2 text-muted fs-12">{{ translate('Messages') }}: {{ $dispute->messages->count() }}</div>
                            @if ($canManageTradeFinance)
                                <form action="{{ route('seller.b2b.trade-finance.disputes.messages.store', $dispute->id) }}" method="POST" class="mt-3">
                                    @csrf
                                    <div class="input-group">
                                        <input type="text" name="message" class="form-control" placeholder="{{ translate('Add dispute message') }}" required>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">{{ translate('Send') }}</button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">{{ translate('No open disputes for this supplier.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-xl-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ translate('Refund Requests') }}</h5>
                    <span class="badge badge-inline badge-soft-secondary">{{ $refunds->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('Refund') }}</th>
                                    <th>{{ translate('Type') }}</th>
                                    <th>{{ translate('Amount') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($refunds as $refund)
                                    <tr>
                                        <td>
                                            <div class="fw-600">#{{ $refund->id }}</div>
                                            <small class="text-muted">{{ $refund->created_at?->format('d M Y, h:i A') ?? '-' }}</small>
                                        </td>
                                        <td>{{ ucwords(str_replace('_', ' ', $refund->refund_type)) }}</td>
                                        <td>{{ number_format((float) $refund->amount, 2) }} {{ $refund->currency }}</td>
                                        <td><span class="badge badge-inline badge-soft-info">{{ ucfirst(str_replace('_', ' ', $refund->status)) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ translate('No refund requests found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ translate('Supplier Finance Queue') }}</h5>
                    <span class="badge badge-inline badge-soft-secondary">{{ $purchaseOrders->total() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('PO') }}</th>
                                    <th>{{ translate('Buyer') }}</th>
                                    <th>{{ translate('Milestones') }}</th>
                                    <th>{{ translate('LC') }}</th>
                                    <th>{{ translate('Disputes') }}</th>
                                    <th>{{ translate('Invoice') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseOrders as $purchaseOrder)
                                    <tr>
                                        <td><a href="{{ route('seller.b2b.purchase-orders.show', $purchaseOrder->id) }}">{{ $purchaseOrder->po_number }}</a></td>
                                        <td>{{ $purchaseOrder->buyerCompany?->company_name ?? '-' }}</td>
                                        <td>{{ $purchaseOrder->milestones->count() }}</td>
                                        <td>{{ $purchaseOrder->lettersOfCredit->count() }}</td>
                                        <td>{{ $purchaseOrder->financeDisputes->where('status', 'open')->count() }}</td>
                                        <td>{{ optional($purchaseOrder->proformaInvoices->sortByDesc('id')->first())->invoice_number ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ translate('No finance queue items found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="aiz-pagination mt-3">{{ $purchaseOrders->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
