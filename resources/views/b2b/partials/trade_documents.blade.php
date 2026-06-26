<div class="card rounded-0 shadow-none border mb-4">
    <div class="card-header">{{ translate('Trade Documents') }}</div>
    <div class="card-body">
        @php
            $tradeDocumentFeeSettings = app(\App\Services\B2BTradeDocumentFeeService::class)->settings();
            $chargeableDocumentTypes = \App\Services\B2BTradeDocumentFeeService::CHARGEABLE_DOCUMENT_TYPES;
        @endphp
        @if (!empty($allowUpload))
            @if ($tradeDocumentFeeSettings['enabled'])
                <div class="alert alert-info">
                    {{ translate('Service Fee') }}:
                    <strong>{{ single_price($tradeDocumentFeeSettings['fixed']) }}</strong>
                    {{ translate('applies to Commercial Invoice, Packing List, Certificate of Origin, and Bill of Lading documents.') }}
                </div>
            @endif
            <form action="{{ route('b2b.trade-documents.store', [$documentTypeKey, $documentable->id]) }}" method="POST" enctype="multipart/form-data" class="mb-4">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Document Type') }}</label>
                        <select name="document_type" class="form-control aiz-selectpicker" required>
                            @foreach (\App\Services\B2BTradeService::DOCUMENT_TYPES as $type)
                                <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Title') }}</label>
                        <input type="text" class="form-control" name="title">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Issued At') }}</label>
                        <input type="date" class="form-control" name="issued_at">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Expires At') }}</label>
                        <input type="date" class="form-control" name="expires_at">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('File') }}</label>
                        <input type="file" class="form-control" name="file" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Notes') }}</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">{{ translate('Upload Document') }}</button>
            </form>
        @endif

        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('Title') }}</th>
                        <th>{{ translate('Dates') }}</th>
                        <th>{{ translate('Service Fee') }}</th>
                        <th>{{ translate('File') }}</th>
                        <th class="text-right">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documentable->documents as $document)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $document->document_type)) }}</td>
                            <td>{{ $document->title ?: '-' }}</td>
                            <td>{{ optional($document->issued_at)->format('d M Y') ?: '-' }} / {{ optional($document->expires_at)->format('d M Y') ?: '-' }}</td>
                            <td>
                                @if (in_array($document->document_type, $chargeableDocumentTypes, true))
                                    {{ single_price($document->service_fee_amount ?? 0) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td><a href="{{ asset($document->file_path) }}" target="_blank">{{ translate('Open') }}</a></td>
                            <td class="text-right">
                                @if (!empty($allowUpload))
                                    <form action="{{ route('b2b.trade-documents.delete', $document->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-danger btn-sm">{{ translate('Delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No documents uploaded yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
