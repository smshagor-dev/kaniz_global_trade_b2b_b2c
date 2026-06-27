<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $claim->claim_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1, h2 { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Insurance Claim {{ $claim->claim_number }}</h1>
    <p class="muted">Generated {{ now()->toDateTimeString() }}</p>

    <table>
        <tr><th>Policy</th><td>{{ $claim->policy?->policy_number }}</td><th>Status</th><td>{{ $claim->status }}</td></tr>
        <tr><th>Claim Type</th><td>{{ $claim->claim_type }}</td><th>Incident Date</th><td>{{ optional($claim->incident_at)->toDateTimeString() }}</td></tr>
        <tr><th>Claim Amount</th><td>{{ $claim->claim_amount }} {{ $claim->currency }}</td><th>Approved Amount</th><td>{{ $claim->approved_amount }} {{ $claim->currency }}</td></tr>
        <tr><th>Settled Amount</th><td>{{ $claim->settled_amount }} {{ $claim->currency }}</td><th>Provider</th><td>{{ $claim->policy?->provider?->name }}</td></tr>
    </table>

    <h2>Summary</h2>
    <p>{{ $claim->summary }}</p>
    <p>{{ $claim->description }}</p>

    <h2>Documents</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Title</th>
                <th>Path</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($claim->documents as $document)
            <tr>
                <td>{{ $document->document_type }}</td>
                <td>{{ $document->title }}</td>
                <td>{{ $document->file_path }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Validation Summary</h2>
    <pre>{{ json_encode($claim->validation_summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</body>
</html>
