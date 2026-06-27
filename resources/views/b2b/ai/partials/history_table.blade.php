<div class="card rounded-0 shadow-none border">
    <div class="card-header bg-white"><h5 class="mb-0">{{ $title }}</h5></div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    @foreach($columns as $field => $label)
                        <th>{{ translate($label) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        @foreach($columns as $field => $label)
                            <td>{{ is_scalar(data_get($record, $field)) || data_get($record, $field) === null ? data_get($record, $field) : json_encode(data_get($record, $field)) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="text-center text-muted">{{ translate('No records yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="aiz-pagination mt-3">{{ $records->links() }}</div>
    </div>
</div>
