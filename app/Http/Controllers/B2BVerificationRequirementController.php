<?php

namespace App\Http\Controllers;

use App\Models\B2BVerificationRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class B2BVerificationRequirementController extends Controller
{
    public function index()
    {
        $requirements = B2BVerificationRequirement::orderBy('sort_order')->orderBy('id')->get();

        return view('backend.b2b.verification_requirements.index', [
            'requirements' => $requirements,
            'companyTypes' => $this->companyTypes(),
            'fieldTypes' => B2BVerificationRequirement::FIELD_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        B2BVerificationRequirement::create($this->validatedData($request));

        flash(translate('Verification requirement created successfully.'))->success();

        return back();
    }

    public function update(Request $request, $id)
    {
        $requirement = B2BVerificationRequirement::findOrFail($id);
        $requirement->update($this->validatedData($request, $requirement->id));

        flash(translate('Verification requirement updated successfully.'))->success();

        return back();
    }

    public function destroy($id)
    {
        $requirement = B2BVerificationRequirement::findOrFail($id);
        $requirement->delete();

        flash(translate('Verification requirement deleted successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('b2b_verification_requirements', 'slug')->ignore($ignoreId),
            ],
            'field_type' => ['required', Rule::in(B2BVerificationRequirement::FIELD_TYPES)],
            'help_text' => 'nullable|string|max:1000',
            'placeholder' => 'nullable|string|max:255',
            'company_types' => 'nullable|array',
            'company_types.*' => ['string', Rule::in(array_keys($this->companyTypes()))],
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['label']);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['company_types'] = $data['company_types'] ?? null;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    protected function companyTypes(): array
    {
        return [
            'buyer' => translate('Buyer'),
            'supplier' => translate('Supplier'),
            'manufacturer' => translate('Manufacturer'),
            'distributor' => translate('Distributor'),
            'wholesaler' => translate('Wholesaler'),
            'retailer' => translate('Retailer'),
        ];
    }
}
