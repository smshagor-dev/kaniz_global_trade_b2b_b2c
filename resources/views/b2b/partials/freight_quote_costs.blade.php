<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ translate('Freight Cost Breakdown') }}</span>
        <span class="badge badge-inline badge-info">{{ $quote->base_currency ?: $quote->currency }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('Description') }}</th>
                        <th>{{ translate('Payer') }}</th>
                        <th>{{ translate('Original') }}</th>
                        <th>{{ translate('Base') }}</th>
                        @if (!empty($editable))
                            <th class="text-right">{{ translate('Actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quote->costs as $line)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $line->cost_type)) }}</td>
                            <td>
                                <div>{{ $line->description ?: '-' }}</div>
                                <div class="small text-muted">
                                    {{ $line->is_billable ? translate('Billable') : translate('Internal') }}
                                    /
                                    {{ $line->is_optional ? translate('Optional') : translate('Required') }}
                                </div>
                            </td>
                            <td>{{ ucfirst($line->payer) }}</td>
                            <td>{{ single_price($line->amount) }} {{ $line->currency }}</td>
                            <td>{{ single_price($line->amountInBaseCurrency()) }} {{ $quote->base_currency ?: $quote->currency }}</td>
                            @if (!empty($editable))
                                <td class="text-right">
                                    <form action="{{ $updateRoute($line) }}" method="POST" class="d-inline-block mr-2">
                                        @csrf
                                        <input type="hidden" name="cost_type" value="{{ $line->cost_type }}">
                                        <input type="hidden" name="description" value="{{ $line->description }}">
                                        <input type="hidden" name="amount" value="{{ $line->amount }}">
                                        <input type="hidden" name="currency" value="{{ $line->currency }}">
                                        <input type="hidden" name="exchange_rate_snapshot" value="{{ $line->exchange_rate_snapshot }}">
                                        <input type="hidden" name="payer" value="{{ $line->payer }}">
                                        <input type="hidden" name="sort_order" value="{{ $line->sort_order }}">
                                        <button type="submit" class="btn btn-soft-warning btn-icon btn-circle btn-sm" title="{{ translate('Re-save line') }}">
                                            <i class="las la-sync"></i>
                                        </button>
                                    </form>
                                    <form action="{{ $deleteRoute($line) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-danger btn-icon btn-circle btn-sm" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ !empty($editable) ? 6 : 5 }}" class="text-center">{{ translate('No cost lines added yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">{{ translate('Freight Total') }}</th>
                        <th colspan="{{ !empty($editable) ? 3 : 2 }}" class="text-right">{{ single_price($quote->total_cost_base_currency ?: $quote->total_cost) }} {{ $quote->base_currency ?: $quote->currency }}</th>
                    </tr>
                    <tr>
                        <th colspan="3">{{ translate('Total Landed Cost') }}</th>
                        <th colspan="{{ !empty($editable) ? 3 : 2 }}" class="text-right">{{ single_price($quote->landed_cost_total) }} {{ $quote->base_currency ?: $quote->currency }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if (!empty($editable))
            <hr>
            <form action="{{ $storeRoute }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Cost Type') }}</label>
                        <select name="cost_type" class="form-control aiz-selectpicker" required>
                            @foreach (\App\Models\B2BFreightQuoteCost::COST_TYPES as $costType)
                                <option value="{{ $costType }}">{{ ucwords(str_replace('_', ' ', $costType)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Description') }}</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Amount') }}</label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Currency') }}</label>
                        <input type="text" name="currency" class="form-control" value="{{ $quote->currency }}">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('FX Rate') }}</label>
                        <input type="number" step="0.000001" min="0.000001" name="exchange_rate_snapshot" class="form-control" value="1">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Payer') }}</label>
                        <select name="payer" class="form-control aiz-selectpicker">
                            @foreach (\App\Models\B2BFreightQuoteCost::PAYERS as $payer)
                                <option value="{{ $payer }}">{{ ucfirst($payer) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Sort Order') }}</label>
                        <input type="number" name="sort_order" min="0" class="form-control" value="0">
                    </div>
                    <div class="col-md-4 form-group pt-4">
                        <label class="aiz-checkbox mr-3">
                            <input type="checkbox" name="is_billable" value="1" checked>
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Billable') }}</span>
                        </label>
                        <label class="aiz-checkbox">
                            <input type="checkbox" name="is_optional" value="1">
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Optional') }}</span>
                        </label>
                    </div>
                    <div class="col-md-3 form-group pt-4">
                        <button type="submit" class="btn btn-primary btn-block">{{ translate('Add Cost Line') }}</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
