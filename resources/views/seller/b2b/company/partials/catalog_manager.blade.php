<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">{{ translate('Create Catalog') }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('seller.b2b.company.catalogs.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>{{ translate('Catalog Title') }}</label>
                    <input type="text" class="form-control" name="title" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>{{ translate('Cover Image') }}</label>
                    <div class="input-group" data-toggle="aizuploader" data-type="image" onclick="return triggerCatalogUploader(event, this)">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                        <input type="hidden" name="cover_image" class="selected-files">
                    </div>
                    <div class="file-preview box sm"></div>
                </div>
                <div class="col-md-4 form-group">
                    <label>{{ translate('Catalog PDF') }}</label>
                    <div class="input-group" data-toggle="aizuploader" data-type="document" onclick="return triggerCatalogUploader(event, this)">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                        <input type="hidden" name="pdf_file" class="selected-files">
                    </div>
                    <div class="file-preview box sm"></div>
                </div>
                <div class="col-md-12 form-group">
                    <label>{{ translate('Description') }}</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="col-md-12 form-group">
                    <label class="aiz-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span class="aiz-square-check"></span>
                        <span>{{ translate('Active') }}</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ translate('Create Catalog') }}</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">{{ translate('Existing Catalogs') }}</h5></div>
    <div class="card-body">
        @forelse ($catalogs as $catalog)
            <form method="POST" action="{{ route('seller.b2b.company.catalogs.update', $catalog->id) }}" class="border rounded p-3 mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Catalog Title') }}</label>
                        <input type="text" class="form-control" name="title" value="{{ $catalog->title }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Cover Image') }}</label>
                        <div class="input-group" data-toggle="aizuploader" data-type="image" onclick="return triggerCatalogUploader(event, this)">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="cover_image" value="{{ $catalog->cover_image }}" class="selected-files">
                        </div>
                        <div class="file-preview box sm"></div>
                        @if ($catalog->cover_image)
                            <small><a href="{{ uploaded_asset($catalog->cover_image) }}" target="_blank">{{ translate('View Current Image') }}</a></small>
                        @endif
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Catalog PDF') }}</label>
                        <div class="input-group" data-toggle="aizuploader" data-type="document" onclick="return triggerCatalogUploader(event, this)">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="pdf_file" value="{{ $catalog->pdf_file }}" class="selected-files">
                        </div>
                        <div class="file-preview box sm"></div>
                        @if ($catalog->pdf_file)
                            <small><a href="{{ uploaded_asset($catalog->pdf_file) }}" target="_blank">{{ translate('View Current PDF') }}</a></small>
                        @endif
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Status') }}</label>
                        <label class="aiz-checkbox d-block mt-2">
                            <input type="checkbox" name="is_active" value="1" @checked($catalog->is_active)>
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Active') }}</span>
                        </label>
                        <div class="small text-muted mt-2">{{ translate('Products linked') }}: {{ $catalog->products()->count() }}</div>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Description') }}</label>
                        <textarea class="form-control" name="description" rows="3">{{ $catalog->description }}</textarea>
                    </div>
                    <div class="col-md-12 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">{{ translate('Slug') }}: {{ $catalog->slug }}</div>
                        <div>
                            <button type="submit" class="btn btn-soft-primary">{{ translate('Update') }}</button>
                            <button type="submit" formaction="{{ route('seller.b2b.company.catalogs.delete', $catalog->id) }}" class="btn btn-soft-danger" onclick="return confirm('{{ translate('Are you sure?') }}')">{{ translate('Delete') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        @empty
            <p class="mb-0 text-muted">{{ translate('No catalogs created yet.') }}</p>
        @endforelse
    </div>
</div>

@once
    @push('catalog_scripts')
        <script type="text/javascript">
            function triggerCatalogUploader(event, element) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                var $element = $(element);
                var type = $element.data('type') ? $element.data('type') : '';
                var selectedFiles = $element.find('.selected-files').val() || '';

                AIZ.uploader.trigger(element, 'input', type, selectedFiles, '');
                return false;
            }
        </script>
    @endpush
@endonce
