<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $policy->policy_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1, h2 { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Insurance Policy {{ $policy->policy_number }}</h1>
    <p class="muted">Generated {{ now()->toDateTimeString() }}</p>

    <table>
        <tr><th>Provider</th><td>{{ $policy->provider?->name }}</td><th>Status</th><td>{{ $policy->status }}</td></tr>
        <tr><th>Insurance Type</th><td>{{ $policy->insurance_type }}</td><th>Transport Mode</th><td>{{ $policy->transport_mode }}</td></tr>
        <tr><th>Coverage Start</th><td>{{ optional($policy->coverage_start)->toDateString() }}</td><th>Coverage End</th><td>{{ optional($policy->coverage_end)->toDateString() }}</td></tr>
        <tr><th>Coverage Amount</th><td>{{ $policy->coverage_amount }} {{ $policy->currency }}</td><th>Premium</th><td>{{ $policy->premium }} {{ $policy->currency }}</td></tr>
        <tr><th>Deductible</th><td>{{ $policy->deductible_amount }} {{ $policy->currency }}</td><th>Insured Value</th><td>{{ $policy->insured_value }} {{ $policy->currency }}</td></tr>
    </table>

    <h2>Trade References</h2>
    <table>
        <tr><th>Buyer Company</th><td>{{ $policy->buyerCompany?->company_name }}</td><th>Supplier Company</th><td>{{ $policy->supplierCompany?->company_name }}</td></tr>
        <tr><th>Purchase Order</th><td>{{ $policy->purchaseOrder?->po_number }}</td><th>Invoice</th><td>{{ $policy->proformaInvoice?->invoice_number }}</td></tr>
        <tr><th>Shipment</th><td>{{ $policy->shipment?->shipment_number }}</td><th>Freight Quote</th><td>{{ $policy->freightQuote?->quote_number }}</td></tr>
    </table>

    <h2>Coverage Details</h2>
    <pre>{{ json_encode($policy->coverage_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
