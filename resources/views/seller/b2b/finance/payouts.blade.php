@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('Supplier Payouts') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} | {{ translate('Available payout balance, payout requests, and settlement history') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Available For Payout') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($summary['available_payout']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Requested Payouts') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($summary['requested_payouts']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Completed Payouts') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($summary['completed_payouts']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Payout Fees') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($summary['payout_fees']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ translate('Available Payout Queue') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Escrow') }}</th>
                            <th>{{ translate('Reference') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Released At') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($releasedEscrows as $escrow)
                            @php($hasSettlement = $escrow->settlements->isNotEmpty())
                            <tr>
                                <td>#{{ $escrow->id }}</td>
                                <td>{{ data_get($escrow->reference, 'invoice_number') ?: class_basename($escrow->reference_type) }}</td>
                                <td>{{ number_format((float) ($escrow->released_amount ?: $escrow->amount), 2) }} {{ $escrow->currency }}</td>
                                <td>{{ $escrow->released_at?->format('d M Y, h:i A') ?? '-' }}</td>
                                <td>
                                    @if ($hasSettlement)
                                        <span class="badge badge-inline badge-soft-info">{{ ucfirst(str_replace('_', ' ', $escrow->settlements->last()->status)) }}</span>
                                    @else
                                        <span class="badge badge-inline badge-soft-success">{{ translate('Ready') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($hasSettlement)
                                        <span class="text-muted">{{ translate('Already requested') }}</span>
                                    @elseif ($canManageTradeFinance)
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm rounded-0"
                                            data-toggle="modal"
                                            data-target="#supplier_payout_request_modal"
                                            data-escrow-id="{{ $escrow->id }}"
                                            data-reference="{{ data_get($escrow->reference, 'invoice_number') ?: class_basename($escrow->reference_type) }}"
                                            data-amount="{{ number_format((float) ($escrow->released_amount ?: $escrow->amount), 2, '.', '') }}"
                                            data-currency="{{ $escrow->currency }}"
                                        >
                                            {{ translate('Request Payout') }}
                                        </button>
                                    @else
                                        <span class="text-muted">{{ translate('No permission') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ translate('No released escrow is available for payout request.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $releasedEscrows->links() }}</div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ translate('Payout History') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Settlement') }}</th>
                            <th>{{ translate('Method') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Fees') }}</th>
                            <th>{{ translate('Net Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($settlements as $settlement)
                            <tr>
                                <td>
                                    <div class="fw-600">#{{ $settlement->id }}</div>
                                    <small class="text-muted">{{ $settlement->requested_at?->format('d M Y, h:i A') ?? '-' }}</small>
                                </td>
                                <td>{{ ucwords(str_replace('_', ' ', $settlement->settlement_method)) }}</td>
                                <td>{{ number_format((float) $settlement->amount, 2) }} {{ $settlement->currency }}</td>
                                <td>{{ number_format((float) $settlement->fees, 2) }} {{ $settlement->currency }}</td>
                                <td>{{ number_format((float) $settlement->net_amount, 2) }} {{ $settlement->currency }}</td>
                                <td><span class="badge badge-inline badge-soft-primary">{{ ucfirst(str_replace('_', ' ', $settlement->status)) }}</span></td>
                                <td>{{ $settlement->reference ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ translate('No payout history found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $settlements->links() }}</div>
        </div>
    </div>
@endsection

@section('modal')
    <div class="modal fade" id="supplier_payout_request_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Request Supplier Payout') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="supplier_payout_request_form" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="text-muted small">{{ translate('Reference') }}</div>
                            <div class="fw-700 js-payout-reference">-</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">{{ translate('Payable Amount') }}</div>
                            <div class="fw-700 js-payout-amount">-</div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Settlement Method') }}</label>
                            <select name="settlement_method" class="form-control" required>
                                <option value="bank_transfer">{{ translate('Bank Transfer') }}</option>
                                <option value="wise">Wise</option>
                                <option value="payoneer">Payoneer</option>
                                <option value="wallet">{{ translate('Wallet') }}</option>
                                <option value="manual">{{ translate('Manual') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Fees') }}</label>
                            <input type="number" name="fees" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Reference') }}</label>
                            <input type="text" name="reference" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Destination Details') }}</label>
                            <textarea name="destination_details" rows="3" class="form-control" placeholder="{{ translate('Bank, wallet, Wise, or account delivery details') }}"></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ translate('Notes') }}</label>
                            <textarea name="notes" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm rounded-0" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-0">{{ translate('Submit Request') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $('#supplier_payout_request_modal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var escrowId = button.data('escrow-id');
            var reference = button.data('reference');
            var amount = button.data('amount');
            var currency = button.data('currency');
            var actionTemplate = @json(url('/supplier/b2b/payouts/escrows/__ID__/request'));

            $('#supplier_payout_request_form').attr('action', actionTemplate.replace('__ID__', escrowId));
            $('.js-payout-reference').text(reference || '-');
            $('.js-payout-amount').text((amount || '0.00') + ' ' + (currency || ''));
        });
    </script>
@endsection
