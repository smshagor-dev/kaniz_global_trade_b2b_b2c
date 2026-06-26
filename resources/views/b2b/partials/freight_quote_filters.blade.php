<form method="GET" class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 form-group">
                <label>{{ translate('Status') }}</label>
                <input type="text" name="status" class="form-control" value="{{ request('status') }}">
            </div>
            <div class="col-md-2 form-group">
                <label>{{ translate('Origin') }}</label>
                <input type="text" name="origin_country" class="form-control" value="{{ request('origin_country') }}">
            </div>
            <div class="col-md-2 form-group">
                <label>{{ translate('Destination') }}</label>
                <input type="text" name="destination_country" class="form-control" value="{{ request('destination_country') }}">
            </div>
            <div class="col-md-2 form-group">
                <label>{{ translate('Port') }}</label>
                <input type="text" name="port" class="form-control" value="{{ request('port') }}">
            </div>
            <div class="col-md-2 form-group">
                <label>{{ translate('Mode') }}</label>
                <input type="text" name="freight_mode" class="form-control" value="{{ request('freight_mode') }}">
            </div>
            <div class="col-md-2 form-group">
                <label>{{ translate('Search') }}</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ translate('Quote / HS / goods') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
    </div>
</form>
