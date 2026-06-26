@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Ports') }}</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">{{ translate('Add Port') }}</div>
                <div class="card-body">
                    <form action="{{ route('admin.b2b.ports.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 form-group"><label>{{ translate('Port Name') }}</label><input type="text" name="name" class="form-control" required></div>
                            <div class="col-md-2 form-group"><label>{{ translate('Country') }}</label><input type="text" name="country" class="form-control" required></div>
                            <div class="col-md-2 form-group"><label>{{ translate('City') }}</label><input type="text" name="city" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ translate('UN/LOCODE') }}</label><input type="text" name="unlocode" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ translate('Port Code') }}</label><input type="text" name="code" class="form-control" required></div>
                            <div class="col-md-2 form-group"><label>{{ translate('Type') }}</label><select name="port_type" class="form-control aiz-selectpicker"><option value="sea">Sea</option><option value="air">Air</option><option value="rail">Rail</option><option value="inland">Inland</option><option value="dry_port">Dry Port</option></select></div>
                            <div class="col-md-2 form-group"><label>{{ translate('Latitude') }}</label><input type="number" step="0.000001" name="latitude" class="form-control"></div>
                            <div class="col-md-2 form-group"><label>{{ translate('Longitude') }}</label><input type="number" step="0.000001" name="longitude" class="form-control"></div>
                            <div class="col-md-3 form-group"><label>{{ translate('Timezone') }}</label><input type="text" name="timezone" class="form-control"></div>
                            <div class="col-md-3 form-group pt-4"><label class="aiz-checkbox"><input type="checkbox" name="is_active" value="1" checked><span class="aiz-square-check"></span><span>{{ translate('Active') }}</span></label></div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Port') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">{{ translate('Bulk Import / Export') }}</div>
                <div class="card-body">
                    <a href="{{ route('admin.b2b.ports.export') }}" class="btn btn-soft-info btn-block mb-3">{{ translate('Export CSV') }}</a>
                    <form action="{{ route('admin.b2b.ports.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>{{ translate('CSV File') }}</label>
                            <input type="file" name="csv_file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">{{ translate('Import CSV') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead><tr><th>{{ translate('Port') }}</th><th>{{ translate('Location') }}</th><th>{{ translate('Type') }}</th><th>{{ translate('Coordinates') }}</th></tr></thead>
                <tbody>
                    @forelse ($ports as $port)
                        <tr>
                            <td><div class="fw-600">{{ $port->name }}</div><div class="small text-muted">{{ $port->code }} / {{ $port->unlocode ?: '-' }}</div></td>
                            <td>{{ $port->city ?: '-' }}, {{ $port->country }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $port->port_type)) }}</td>
                            <td>{{ $port->latitude ?: '-' }}, {{ $port->longitude ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ translate('No ports found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $ports->links() }}</div>
        </div>
    </div>
@endsection
