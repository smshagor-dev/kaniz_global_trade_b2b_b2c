<?php

namespace App\Http\Controllers;

use App\Models\B2BHsCode;
use Illuminate\Http\Request;

class B2BHsCodeController extends Controller
{
    public function index()
    {
        return view('backend.b2b.hs_codes.index', [
            'codes' => B2BHsCode::latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        B2BHsCode::create((new B2BHsCode())->filterPersistable($this->validatedData($request)));
        flash(translate('HS code created successfully.'))->success();

        return back();
    }

    public function update(Request $request, $id)
    {
        $code = B2BHsCode::findOrFail($id);
        $code->update($code->filterPersistable($this->validatedData($request)));
        flash(translate('HS code updated successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'hs_code' => 'required|string|max:60',
            'description' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'duty_percent' => 'nullable|numeric|min:0',
            'vat_gst_percent' => 'nullable|numeric|min:0',
            'restrictions' => 'nullable|string',
            'is_dangerous_goods' => 'nullable|boolean',
            'required_documents' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]) + [
            'required_documents' => $this->normalizeList($request->input('required_documents')),
            'is_dangerous_goods' => $request->boolean('is_dangerous_goods'),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    protected function normalizeList(?string $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value)), fn ($item) => filled($item)));
    }
}
