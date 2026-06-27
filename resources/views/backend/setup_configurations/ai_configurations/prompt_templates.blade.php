@extends('backend.layouts.app')

@section('content')
<div class="row pt-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Create Prompt Template') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('ai-prompt-templates.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>{{ translate('Module') }}</label>
                        <input type="text" class="form-control" name="module" placeholder="product_generation" required>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Name') }}</label>
                        <input type="text" class="form-control" name="name" placeholder="product_add_edit_prompt" required>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('System Prompt') }}</label>
                        <textarea class="form-control" rows="4" name="system_prompt"></textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('User Prompt Template') }}</label>
                        <textarea class="form-control" rows="6" name="user_prompt_template" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Variables') }}</label>
                        <input type="text" class="form-control" name="variables" placeholder="product_name,language,prompt_fields">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Version') }}</label>
                        <input type="number" class="form-control" name="version" value="1" min="1" required>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Create Template') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Prompt Templates') }}</h5></div>
            <div class="card-body">
                @forelse ($prompt_templates as $prompt_template)
                    <div class="border rounded p-3 mb-4">
                        <form action="{{ route('ai-prompt-templates.update', encrypt($prompt_template->id)) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>{{ translate('Module') }}</label>
                                    <input type="text" class="form-control" name="module" value="{{ $prompt_template->module }}" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>{{ translate('Name') }}</label>
                                    <input type="text" class="form-control" name="name" value="{{ $prompt_template->name }}" required>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>{{ translate('Version') }}</label>
                                    <input type="number" class="form-control" name="version" value="{{ $prompt_template->version }}" min="1" required>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>{{ translate('Active') }}</label><br>
                                    <label class="aiz-switch aiz-switch-success mb-0"><input type="checkbox" name="is_active" value="1" @checked($prompt_template->is_active)><span></span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('System Prompt') }}</label>
                                <textarea class="form-control" rows="4" name="system_prompt">{{ $prompt_template->system_prompt }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('User Prompt Template') }}</label>
                                <textarea class="form-control" rows="6" name="user_prompt_template" required>{{ $prompt_template->user_prompt_template }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Variables') }}</label>
                                <input type="text" class="form-control" name="variables" value="{{ implode(',', (array) $prompt_template->variables) }}">
                                <small class="text-muted">{{ translate('Keep placeholders like {product_name} intact when updating prompts.') }}</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ translate('Save Template') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="mb-0">{{ translate('No prompt templates found.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
