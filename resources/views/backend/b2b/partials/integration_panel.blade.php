@php
    $panelId = $panelId ?? ('integration-panel-' . ($model->id ?? uniqid()));
    $configured = $integration['configured'] ?? false;
    $urls = $integration['urls'] ?? [];
    $docs = $integration['docs'] ?? [];
    $health = $integration['health'] ?? [];
    $status = $integration['connection_status'] ?? 'not_configured';
    $statusMap = [
        'connected' => 'success',
        'not_configured' => 'secondary',
        'authentication_failed' => 'danger',
        'invalid_credentials' => 'danger',
        'webhook_not_verified' => 'warning',
        'sandbox' => 'warning',
        'production' => 'dark',
    ];
@endphp

<div class="card border-0 shadow-none bg-light">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-1">{{ translate('Integration') }}</h6>
                <div class="small text-muted">{{ translate('Professional webhook, health, and connection configuration') }}</div>
            </div>
            <div class="text-right">
                <span class="badge badge-inline badge-{{ $statusMap[$status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                <span class="badge badge-inline badge-{{ ($integration['environment_label'] ?? 'sandbox') === 'sandbox' ? 'warning' : 'dark' }}">{{ ucfirst($integration['environment_label'] ?? 'sandbox') }}</span>
            </div>
        </div>

        @if (!$configured)
            <div class="alert alert-warning mb-0">
                {{ translate('Webhook URLs become visible after valid credentials are saved.') }}
            </div>
        @else
            <div class="row">
                <div class="col-lg-7">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ([
                                    'webhook_url' => 'Webhook URL',
                                    'callback_url' => 'Callback URL',
                                    'tracking_webhook_url' => 'Tracking Webhook URL',
                                    'shipment_webhook_url' => 'Shipment Webhook URL',
                                    'pickup_webhook_url' => 'Pickup Webhook URL',
                                    'test_connection_url' => 'Test Connection URL',
                                ] as $key => $label)
                                    <tr>
                                        <th class="border-0 pl-0">{{ translate($label) }}</th>
                                        <td class="border-0">
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control integration-copy-target" id="{{ $panelId }}-{{ $key }}" value="{{ $urls[$key] ?? '' }}" readonly>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-soft-primary" onclick="copyIntegrationValue('{{ $panelId }}-{{ $key }}')">{{ translate('Copy') }}</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-soft-primary btn-sm mr-2" onclick="copyIntegrationGroup('{{ $panelId }}')">{{ translate('Copy All') }}</button>
                        <a href="#{{ $panelId }}-documentation" class="btn btn-soft-info btn-sm">{{ translate('Open Documentation') }}</a>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">{{ translate('Webhook Secret') }}</div>
                        <div class="card-body">
                            <div class="input-group mb-3">
                                <input type="password" id="{{ $panelId }}-secret" class="form-control" value="{{ $model->webhook_secret ?: '' }}" readonly>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-soft-secondary" onclick="toggleIntegrationSecret('{{ $panelId }}-secret')">{{ translate('Reveal/Hide') }}</button>
                                    <button type="button" class="btn btn-soft-primary" onclick="copyIntegrationValue('{{ $panelId }}-secret')">{{ translate('Copy Secret') }}</button>
                                </div>
                            </div>
                            <form action="{{ $regenerateSecretRoute }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">{{ translate('Regenerate Secret') }}</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="row">
                        @foreach ([
                            'http_status' => 'HTTP Status',
                            'response_time_ms' => 'Response Time',
                            'last_successful_connection' => 'Last Successful Connection',
                            'last_failed_connection' => 'Last Failed Connection',
                            'last_sync_time' => 'Last Sync Time',
                            'last_webhook_received' => 'Last Webhook Received',
                            'api_uptime' => 'API Uptime',
                            'success_rate' => 'Success Rate',
                            'average_response_time_ms' => 'Average Response Time',
                            'failed_requests' => 'Failed Requests',
                        ] as $metricKey => $metricLabel)
                            <div class="col-6 mb-3">
                                <div class="border rounded p-2 h-100 bg-white">
                                    <div class="small text-muted">{{ translate($metricLabel) }}</div>
                                    <div class="fw-600">
                                        @php $value = $health[$metricKey] ?? null; @endphp
                                        @if (in_array($metricKey, ['api_uptime', 'success_rate'], true))
                                            {{ $value !== null ? $value . '%' : '-' }}
                                        @elseif (str_contains($metricKey, 'response_time'))
                                            {{ $value ? $value . ' ms' : '-' }}
                                        @elseif ($value instanceof \Illuminate\Support\Carbon)
                                            {{ $value->format('d M Y H:i') }}
                                        @else
                                            {{ $value ?: '-' }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">{{ translate('Test Actions') }}</div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap">
                                <form action="{{ $testConnectionRoute }}" method="POST" class="mr-2 mb-2">@csrf<button type="submit" class="btn btn-soft-primary btn-sm">{{ translate('Test API Connection') }}</button></form>
                                <form action="{{ $testAuthenticationRoute }}" method="POST" class="mr-2 mb-2">@csrf<button type="submit" class="btn btn-soft-info btn-sm">{{ translate('Test Authentication') }}</button></form>
                                <form action="{{ $verifyCredentialsRoute }}" method="POST" class="mr-2 mb-2">@csrf<button type="submit" class="btn btn-soft-success btn-sm">{{ translate('Verify Credentials') }}</button></form>
                                <form action="{{ $testWebhookRoute }}" method="POST" class="mr-2 mb-2">@csrf<button type="submit" class="btn btn-soft-warning btn-sm">{{ translate('Test Webhook') }}</button></form>
                                <form action="{{ $sendSampleWebhookRoute }}" method="POST" class="mb-2">@csrf<button type="submit" class="btn btn-soft-secondary btn-sm">{{ translate('Send Sample Webhook') }}</button></form>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">{{ translate('Event Subscription') }}</div>
                        <div class="card-body">
                            <form action="{{ $updateEventsRoute }}" method="POST">
                                @csrf
                                <div class="row">
                                    @foreach (\App\Services\B2BIntegrationManagementService::EVENT_OPTIONS as $event)
                                        <div class="col-md-6">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" name="integration_events[]" value="{{ $event }}" @checked(in_array($event, $integration['events'] ?? [], true))>
                                                <span class="aiz-square-check"></span>
                                                <span>{{ ucwords(str_replace('_', ' ', $event)) }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mt-2">{{ translate('Save Events') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3" id="{{ $panelId }}-documentation">
                <div class="card-header">{{ translate('Integration Documentation') }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>{{ translate('Authentication Method') }}:</strong> {{ $docs['authentication_method'] ?? '-' }}</p>
                            <p><strong>{{ translate('Signature Header') }}:</strong> {{ $docs['signature_header'] ?? '-' }}</p>
                            <p><strong>{{ translate('Required Headers') }}:</strong></p>
                            <ul class="pl-3 mb-0">
                                @foreach (($docs['required_headers'] ?? []) as $header)
                                    <li>{{ $header }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <p><strong>{{ translate('Required Events') }}:</strong></p>
                            <ul class="pl-3 mb-0">
                                @foreach (($docs['required_events'] ?? []) as $event)
                                    <li>{{ ucwords(str_replace('_', ' ', $event)) }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <p><strong>{{ translate('Sample Payload') }}:</strong></p>
                            <pre class="bg-soft-secondary p-2 rounded small">{{ json_encode($docs['sample_payload'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            <p><strong>{{ translate('Sample Response') }}:</strong></p>
                            <pre class="bg-soft-secondary p-2 rounded small">{{ json_encode($docs['sample_response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@once
    @push('script')
        <script>
            function copyIntegrationValue(id) {
                const input = document.getElementById(id);
                if (!input) return;
                input.select();
                input.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(input.value);
            }
            function copyIntegrationGroup(prefix) {
                const values = Array.from(document.querySelectorAll('[id^="' + prefix + '-"]'))
                    .filter(el => el.classList.contains('integration-copy-target'))
                    .map(el => el.value)
                    .filter(Boolean)
                    .join('\n');
                navigator.clipboard.writeText(values);
            }
            function toggleIntegrationSecret(id) {
                const input = document.getElementById(id);
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
            }
        </script>
    @endpush
@endonce
