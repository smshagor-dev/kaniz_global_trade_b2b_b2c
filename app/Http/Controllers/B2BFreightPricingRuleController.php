<?php

namespace App\Http\Controllers;

use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightPricingRule;
use App\Services\B2BFreightService;
use App\Services\B2BTradeService;
use Illuminate\Http\Request;

class B2BFreightPricingRuleController extends Controller
{
    public function index()
    {
        return view('backend.b2b.freight_pricing_rules.index', [
            'rules' => B2BFreightPricingRule::with('forwarder')->latest()->paginate(20),
            'forwarders' => B2BFreightForwarder::orderBy('name')->get(),
            'freightModes' => B2BFreightService::FREIGHT_MODES,
            'serviceTypes' => B2BFreightService::SERVICE_TYPES,
            'incoterms' => B2BTradeService::INCOTERMS,
        ]);
    }

    public function store(Request $request)
    {
        B2BFreightPricingRule::create((new B2BFreightPricingRule())->filterPersistable($this->validatedData($request)));
        flash(translate('Freight pricing rule created successfully.'))->success();

        return back();
    }

    public function update(Request $request, $id)
    {
        $rule = B2BFreightPricingRule::findOrFail($id);
        $rule->update($rule->filterPersistable($this->validatedData($request)));
        flash(translate('Freight pricing rule updated successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'forwarder_id' => 'nullable|exists:b2b_freight_forwarders,id',
            'freight_mode' => 'nullable|string|max:30',
            'service_type' => 'nullable|string|max:40',
            'origin_country' => 'nullable|string|max:100',
            'destination_country' => 'nullable|string|max:100',
            'container_type' => 'nullable|string|max:40',
            'incoterm' => 'nullable|string|max:10',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'min_volume' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'base_price' => 'required|numeric|min:0',
            'price_per_kg' => 'nullable|numeric|min:0',
            'price_per_cbm' => 'nullable|numeric|min:0',
            'fuel_surcharge_percent' => 'nullable|numeric|min:0',
            'platform_fee_percent' => 'nullable|numeric|min:0',
            'platform_fee_fixed' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:20',
            'active' => 'nullable|boolean',
        ]) + [
            'active' => $request->boolean('active', true),
        ];
    }
}
