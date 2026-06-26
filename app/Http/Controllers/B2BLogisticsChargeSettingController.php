<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Services\B2BEscrowFeeService;
use App\Services\B2BInspectionServiceChargeService;
use App\Services\B2BLogisticsChargeService;
use App\Services\B2BOrderPlatformFeeService;
use App\Services\B2BSampleProcessingFeeService;
use App\Services\B2BTradeDocumentFeeService;
use Artisan;
use Illuminate\Http\Request;

class B2BLogisticsChargeSettingController extends Controller
{
    public function __construct(
        protected B2BLogisticsChargeService $logisticsChargeService,
        protected B2BOrderPlatformFeeService $orderPlatformFeeService,
        protected B2BEscrowFeeService $escrowFeeService,
        protected B2BSampleProcessingFeeService $sampleProcessingFeeService,
        protected B2BInspectionServiceChargeService $inspectionServiceChargeService,
        protected B2BTradeDocumentFeeService $tradeDocumentFeeService
    )
    {
    }

    public function index()
    {
        return view('backend.b2b.logistics_charge_settings.index', [
            'shippingSettings' => $this->logisticsChargeService->settings('shipping'),
            'orderFeeSettings' => $this->orderPlatformFeeService->settings(),
            'escrowFeeSettings' => $this->escrowFeeService->settings(),
            'sampleProcessingFeeSettings' => $this->sampleProcessingFeeService->settings(),
            'inspectionServiceChargeSettings' => $this->inspectionServiceChargeService->settings(),
            'tradeDocumentFeeSettings' => $this->tradeDocumentFeeService->settings(),
            'shippingChargeTypes' => B2BLogisticsChargeService::CHARGE_TYPES,
            'orderChargeTypes' => B2BOrderPlatformFeeService::CHARGE_TYPES,
            'escrowChargeTypes' => B2BEscrowFeeService::CHARGE_TYPES,
            'sampleChargeTypes' => B2BSampleProcessingFeeService::CHARGE_TYPES,
            'inspectionChargeTypes' => B2BInspectionServiceChargeService::CHARGE_TYPES,
            'tradeDocumentChargeTypes' => B2BTradeDocumentFeeService::CHARGE_TYPES,
            'currencyCode' => get_system_default_currency()->code,
        ]);
    }

    public function update(Request $request)
    {
        $section = $request->validate([
            'config_section' => 'required|in:shipping,order,escrow,sample,inspection,trade_document',
        ])['config_section'];

        if ($section === 'shipping') {
            $data = $request->validate([
                'b2b_shipping_site_charge_enabled' => 'nullable|boolean',
                'b2b_shipping_site_charge_type' => 'required|in:fixed,percentage',
                'b2b_shipping_site_charge_percent' => 'nullable|numeric|min:0|max:1000',
                'b2b_shipping_site_charge_fixed' => 'nullable|numeric|min:0',
            ]);

            $settings = [
                'b2b_shipping_site_charge_enabled' => $request->boolean('b2b_shipping_site_charge_enabled'),
                'b2b_shipping_site_charge_type' => $data['b2b_shipping_site_charge_type'],
                'b2b_shipping_site_charge_percent' => (float) ($data['b2b_shipping_site_charge_percent'] ?? 0),
                'b2b_shipping_site_charge_fixed' => (float) ($data['b2b_shipping_site_charge_fixed'] ?? 0),
            ];
        } elseif ($section === 'order') {
            $data = $request->validate([
                'b2b_order_platform_fee_enabled' => 'nullable|boolean',
                'b2b_order_platform_fee_type' => 'required|in:fixed,percentage',
                'b2b_order_platform_fee_percent' => 'nullable|numeric|min:0|max:1000',
                'b2b_order_platform_fee_fixed' => 'nullable|numeric|min:0',
            ]);

            $settings = [
                'b2b_order_platform_fee_enabled' => $request->boolean('b2b_order_platform_fee_enabled'),
                'b2b_order_platform_fee_type' => $data['b2b_order_platform_fee_type'],
                'b2b_order_platform_fee_percent' => (float) ($data['b2b_order_platform_fee_percent'] ?? 0),
                'b2b_order_platform_fee_fixed' => (float) ($data['b2b_order_platform_fee_fixed'] ?? 0),
            ];
        } elseif ($section === 'escrow') {
            $data = $request->validate([
                'b2b_escrow_fee_enabled' => 'nullable|boolean',
                'b2b_escrow_fee_type' => 'required|in:fixed,percentage',
                'b2b_escrow_fee_percent' => 'nullable|numeric|min:0|max:1000',
                'b2b_escrow_fee_fixed' => 'nullable|numeric|min:0',
            ]);

            $settings = [
                'b2b_escrow_fee_enabled' => $request->boolean('b2b_escrow_fee_enabled'),
                'b2b_escrow_fee_type' => $data['b2b_escrow_fee_type'],
                'b2b_escrow_fee_percent' => (float) ($data['b2b_escrow_fee_percent'] ?? 0),
                'b2b_escrow_fee_fixed' => (float) ($data['b2b_escrow_fee_fixed'] ?? 0),
            ];
        } elseif ($section === 'sample') {
            $data = $request->validate([
                'b2b_sample_processing_fee_enabled' => 'nullable|boolean',
                'b2b_sample_processing_fee_type' => 'required|in:fixed,percentage',
                'b2b_sample_processing_fee_percent' => 'nullable|numeric|min:0|max:1000',
                'b2b_sample_processing_fee_fixed' => 'nullable|numeric|min:0',
            ]);

            $settings = [
                'b2b_sample_processing_fee_enabled' => $request->boolean('b2b_sample_processing_fee_enabled'),
                'b2b_sample_processing_fee_type' => $data['b2b_sample_processing_fee_type'],
                'b2b_sample_processing_fee_percent' => (float) ($data['b2b_sample_processing_fee_percent'] ?? 0),
                'b2b_sample_processing_fee_fixed' => (float) ($data['b2b_sample_processing_fee_fixed'] ?? 0),
            ];
        } elseif ($section === 'inspection') {
            $data = $request->validate([
                'b2b_inspection_service_charge_enabled' => 'nullable|boolean',
                'b2b_inspection_service_charge_type' => 'required|in:fixed,percentage',
                'b2b_inspection_service_charge_percent' => 'nullable|numeric|min:0|max:1000',
                'b2b_inspection_service_charge_fixed' => 'nullable|numeric|min:0',
            ]);

            $settings = [
                'b2b_inspection_service_charge_enabled' => $request->boolean('b2b_inspection_service_charge_enabled'),
                'b2b_inspection_service_charge_type' => $data['b2b_inspection_service_charge_type'],
                'b2b_inspection_service_charge_percent' => (float) ($data['b2b_inspection_service_charge_percent'] ?? 0),
                'b2b_inspection_service_charge_fixed' => (float) ($data['b2b_inspection_service_charge_fixed'] ?? 0),
            ];
        } else {
            $data = $request->validate([
                'b2b_trade_document_fee_enabled' => 'nullable|boolean',
                'b2b_trade_document_fee_type' => 'required|in:fixed,percentage',
                'b2b_trade_document_fee_percent' => 'nullable|numeric|min:0|max:1000',
                'b2b_trade_document_fee_fixed' => 'nullable|numeric|min:0',
            ]);

            $settings = [
                'b2b_trade_document_fee_enabled' => $request->boolean('b2b_trade_document_fee_enabled'),
                'b2b_trade_document_fee_type' => $data['b2b_trade_document_fee_type'],
                'b2b_trade_document_fee_percent' => (float) ($data['b2b_trade_document_fee_percent'] ?? 0),
                'b2b_trade_document_fee_fixed' => (float) ($data['b2b_trade_document_fee_fixed'] ?? 0),
            ];
        }

        foreach ($settings as $type => $value) {
            BusinessSetting::updateOrCreate(
                ['type' => $type],
                ['value' => $value]
            );
        }

        Artisan::call('cache:clear');

        flash(translate('Global B2B configuration updated successfully.'))->success();

        return back();
    }
}
