@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3">{{ translate('Create Proforma Invoice') }}</h1>
                <p class="mb-0 text-muted">{{ $purchaseOrder->po_number }} / {{ $purchaseOrder->buyerCompany?->company_name }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('seller.b2b.proforma-invoices.store', $purchaseOrder->id) }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-header">{{ translate('Invoice Details') }}</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Currency') }}</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', $purchaseOrder->currency) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Valid Until') }}</label>
                            <input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', optional(now()->addDays(7))->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Status') }}</label>
                            <select name="status" class="form-control aiz-selectpicker">
                                <option value="draft">{{ translate('Draft') }}</option>
                                <option value="sent">{{ translate('Sent') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Incoterm') }}</label>
                            <select name="incoterm" class="form-control aiz-selectpicker" required>
                                @foreach (\App\Services\B2BTradeService::INCOTERMS as $incoterm)
                                    <option value="{{ $incoterm }}" @selected(old('incoterm', $purchaseOrder->incoterms) === $incoterm)>{{ $incoterm }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <table class="table aiz-table mb-3">
                    <thead>
                        <tr>
                            <th>{{ translate('Product') }}</th>
                            <th>{{ translate('Qty') }}</th>
                            <th>{{ translate('Unit') }}</th>
                            <th>{{ translate('Unit Price') }}</th>
                            <th>{{ translate('Tax') }}</th>
                            <th>{{ translate('Discount') }}</th>
                            <th>{{ translate('Line Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseOrder->items as $index => $item)
                            <tr>
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                    <input type="text" name="items[{{ $index }}][product_name]" class="form-control" value="{{ $item->product_name }}" required>
                                    <textarea name="items[{{ $index }}][description]" class="form-control mt-2" rows="2">{{ $item->description }}</textarea>
                                </td>
                                <td><input type="number" step="0.01" name="items[{{ $index }}][quantity]" class="form-control invoice-qty" value="{{ $item->quantity }}" required></td>
                                <td><input type="text" name="items[{{ $index }}][unit]" class="form-control" value="{{ $item->unit }}"></td>
                                <td><input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="form-control invoice-unit-price" value="{{ $item->unit_price }}" required></td>
                                <td><input type="number" step="0.01" name="items[{{ $index }}][tax_amount]" class="form-control" value="0"></td>
                                <td><input type="number" step="0.01" name="items[{{ $index }}][discount_amount]" class="form-control" value="0"></td>
                                <td><input type="number" step="0.01" name="items[{{ $index }}][line_total]" class="form-control invoice-line-total" value="{{ $item->line_total }}" required></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="row">
                    <div class="col-md-4 offset-md-8">
                        <div class="form-group">
                            <label>{{ translate('Tax Amount') }}</label>
                            <input type="number" step="0.01" name="tax_amount" class="form-control js-invoice-tax" value="{{ old('tax_amount', 0) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Shipping Amount') }}</label>
                            <input type="number" step="0.01" name="shipping_amount" class="form-control js-invoice-shipping" value="{{ old('shipping_amount', 0) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Discount Amount') }}</label>
                            <input type="number" step="0.01" name="discount_amount" class="form-control js-invoice-discount" value="{{ old('discount_amount', 0) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Buyer Payable Total') }}</label>
                            <input type="text" class="form-control js-buyer-payable" value="0.00" readonly>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Platform Service Fee') }}</label>
                            <input type="text" class="form-control js-platform-fee" value="0.00" readonly>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Escrow Fee') }}</label>
                            <input type="text" class="form-control js-escrow-fee" value="0.00" readonly>
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ translate('Supplier Payout') }}</label>
                            <input type="text" class="form-control js-supplier-payout" value="0.00" readonly>
                        </div>
                    </div>
                </div>

                <div class="alert alert-soft-warning">
                    <div><strong>{{ translate('Payment Flow') }}:</strong> {{ translate('Buyer pays full invoice amount to Kaniz Global Trade.') }}</div>
                    <div class="mt-1">{{ translate('Platform service fee is deducted from order value before supplier payout is settled.') }}</div>
                    <div class="mt-1">{{ translate('Escrow fee is added on top of the buyer payment when escrow is enabled.') }}</div>
                </div>

                <div class="form-group">
                    <label>{{ translate('Notes') }}</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">{{ translate('Create Invoice') }}</button>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <script type="text/javascript">
        (function () {
            var feeSettings = @json($orderPlatformFeeSettings);
            var escrowSettings = @json($escrowFeeSettings);
            var qtyFields = document.querySelectorAll('.invoice-qty');
            var unitPriceFields = document.querySelectorAll('.invoice-unit-price');
            var lineTotalFields = document.querySelectorAll('.invoice-line-total');
            var taxInput = document.querySelector('.js-invoice-tax');
            var shippingInput = document.querySelector('.js-invoice-shipping');
            var discountInput = document.querySelector('.js-invoice-discount');
            var buyerPayableInput = document.querySelector('.js-buyer-payable');
            var platformFeeInput = document.querySelector('.js-platform-fee');
            var escrowFeeInput = document.querySelector('.js-escrow-fee');
            var supplierPayoutInput = document.querySelector('.js-supplier-payout');

            function numberValue(input) {
                var value = parseFloat((input && input.value) || 0);
                return isNaN(value) ? 0 : value;
            }

            function calculatePlatformFee(orderValue) {
                if (!feeSettings.enabled) {
                    return 0;
                }

                if (feeSettings.type === 'percentage') {
                    return orderValue * (parseFloat(feeSettings.percent || 0) / 100);
                }

                return parseFloat(feeSettings.fixed || 0);
            }

            function calculateEscrowFee(orderValue) {
                if (!escrowSettings.enabled) {
                    return 0;
                }

                if (escrowSettings.type === 'percentage') {
                    return orderValue * (parseFloat(escrowSettings.percent || 0) / 100);
                }

                return parseFloat(escrowSettings.fixed || 0);
            }

            function syncSummary() {
                var subtotal = 0;
                lineTotalFields.forEach(function (field) {
                    subtotal += numberValue(field);
                });

                var orderValue = subtotal + numberValue(taxInput) + numberValue(shippingInput) - numberValue(discountInput);
                var platformFee = Math.min(calculatePlatformFee(orderValue), orderValue);
                var escrowFee = calculateEscrowFee(orderValue);
                var buyerPayable = orderValue + escrowFee;
                var supplierPayout = orderValue - platformFee;

                buyerPayableInput.value = buyerPayable.toFixed(2);
                platformFeeInput.value = platformFee.toFixed(2);
                escrowFeeInput.value = escrowFee.toFixed(2);
                supplierPayoutInput.value = supplierPayout.toFixed(2);
            }

            function syncLineTotal(row) {
                var qty = parseFloat(row.querySelector('.invoice-qty').value || 0);
                var unitPrice = parseFloat(row.querySelector('.invoice-unit-price').value || 0);
                row.querySelector('.invoice-line-total').value = (qty * unitPrice).toFixed(2);
                syncSummary();
            }

            qtyFields.forEach(function (field) {
                field.addEventListener('input', function () {
                    syncLineTotal(this.closest('tr'));
                });
            });

            unitPriceFields.forEach(function (field) {
                field.addEventListener('input', function () {
                    syncLineTotal(this.closest('tr'));
                });
            });

            [taxInput, shippingInput, discountInput].forEach(function (field) {
                field.addEventListener('input', syncSummary);
            });

            syncSummary();
        })();
    </script>
@endsection
