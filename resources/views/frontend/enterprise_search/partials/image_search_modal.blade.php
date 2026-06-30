<div class="modal fade" id="globalSearchImageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Image Search') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="global-search-image-form" action="{{ route('global.search.image') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label">{{ translate('Upload product image') }}</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">{{ translate('Analyze image') }}</button>
                </form>
                <div id="global-search-image-feedback" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>
