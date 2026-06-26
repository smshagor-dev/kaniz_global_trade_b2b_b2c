<?php

namespace App\Services;

use App\Models\B2BFreightQuote;
use App\Models\B2BFreightQuoteCost;
use App\Models\B2BHsCode;
use Illuminate\Support\Arr;

class B2BFreightCostService
{
    public function __construct(
        protected B2BLandedCostService $landedCostService,
        protected B2BInspectionServiceChargeService $inspectionServiceChargeService
    )
    {
    }

    public function storeLine(B2BFreightQuote $quote, array $data): B2BFreightQuoteCost
    {
        $line = $quote->costs()->create((new B2BFreightQuoteCost())->filterPersistable($this->normalizeLine($quote, $data)));
        $this->inspectionServiceChargeService->syncForQuote($quote->fresh('costs'));
        $this->recalculate($quote->fresh());

        return $line->fresh();
    }

    public function updateLine(B2BFreightQuoteCost $line, array $data): B2BFreightQuoteCost
    {
        $quote = $line->freightQuote;
        $line->update($line->filterPersistable($this->normalizeLine($quote, array_merge($line->toArray(), $data))));
        $this->inspectionServiceChargeService->syncForQuote($quote->fresh('costs'));
        $this->recalculate($quote->fresh());

        return $line->fresh();
    }

    public function deleteLine(B2BFreightQuoteCost $line): void
    {
        $quote = $line->freightQuote;
        $line->delete();
        $this->inspectionServiceChargeService->syncForQuote($quote->fresh('costs'));
        $this->recalculate($quote->fresh());
    }

    public function applyPricingResult(B2BFreightQuote $quote, array $pricingResult): void
    {
        $map = [
            'base_freight_cost' => 'Rule based base freight',
            'fuel_surcharge' => 'Rule based fuel surcharge',
            'platform_service_fee' => 'Platform service fee',
        ];

        foreach ($map as $costType => $description) {
            $amount = (float) ($pricingResult[$costType] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $quote->costs()->updateOrCreate(
                ['cost_type' => $costType],
                (new B2BFreightQuoteCost())->filterPersistable([
                    'description' => $description,
                    'amount' => $amount,
                    'currency' => $pricingResult['currency'] ?? $quote->currency,
                    'exchange_rate_snapshot' => 1,
                    'payer' => $costType === 'platform_service_fee' ? 'platform' : 'buyer',
                    'is_billable' => true,
                    'is_optional' => false,
                    'sort_order' => 10,
                ])
            );
        }

        if (!empty($pricingResult['rule'])) {
            $quote->update($quote->filterPersistable([
                'pricing_rule_id' => $pricingResult['rule']->id,
                'currency' => $pricingResult['currency'] ?? $quote->currency,
            ]));
        }

        $this->recalculate($quote->fresh());
    }

    public function applyHsCode(B2BFreightQuote $quote): void
    {
        if (!$quote->hs_code) {
            return;
        }

        $hsCode = B2BHsCode::query()
            ->where('is_active', true)
            ->where('hs_code', $quote->hs_code)
            ->where(function ($query) use ($quote) {
                $query->whereNull('country')->orWhere('country', $quote->destination_country);
            })
            ->latest('id')
            ->first();

        if (!$hsCode) {
            return;
        }

        $customsBase = (float) $quote->freight_cost + (float) $quote->insurance_cost;
        $dutyAmount = round($customsBase * ((float) $hsCode->duty_percent / 100), 2);
        $vatAmount = round(($customsBase + $dutyAmount) * ((float) $hsCode->vat_gst_percent / 100), 2);

        $quote->costs()->updateOrCreate(
            ['cost_type' => 'customs_duty'],
            (new B2BFreightQuoteCost())->filterPersistable([
                'description' => 'HS code customs duty',
                'amount' => $dutyAmount,
                'currency' => $quote->currency,
                'exchange_rate_snapshot' => 1,
                'payer' => 'buyer',
                'is_billable' => true,
                'is_optional' => false,
                'sort_order' => 80,
            ])
        );

        $quote->costs()->updateOrCreate(
            ['cost_type' => 'vat_gst'],
            (new B2BFreightQuoteCost())->filterPersistable([
                'description' => 'HS code VAT/GST',
                'amount' => $vatAmount,
                'currency' => $quote->currency,
                'exchange_rate_snapshot' => 1,
                'payer' => 'buyer',
                'is_billable' => true,
                'is_optional' => false,
                'sort_order' => 90,
            ])
        );

        $quote->update($quote->filterPersistable(['hs_code_record_id' => $hsCode->id]));
        $this->recalculate($quote->fresh());
    }

    public function syncDefaultCostsFromQuote(B2BFreightQuote $quote): void
    {
        $defaults = [
            'base_freight_cost' => ['amount' => (float) $quote->freight_cost, 'description' => 'Base freight'],
            'insurance' => ['amount' => (float) $quote->insurance_cost, 'description' => 'Insurance'],
            'customs_clearance_fee' => ['amount' => (float) $quote->customs_estimate, 'description' => 'Customs estimate'],
        ];

        foreach ($defaults as $costType => $config) {
            if ($config['amount'] <= 0) {
                continue;
            }

            $quote->costs()->updateOrCreate(
                ['cost_type' => $costType],
                (new B2BFreightQuoteCost())->filterPersistable([
                    'description' => $config['description'],
                    'amount' => $config['amount'],
                    'currency' => $quote->currency,
                    'exchange_rate_snapshot' => 1,
                    'payer' => 'buyer',
                    'is_billable' => true,
                    'is_optional' => false,
                    'sort_order' => 10,
                ])
            );
        }

        $this->applyHsCode($quote->fresh());
        $this->recalculate($quote->fresh());
    }

    public function recalculate(B2BFreightQuote $quote): array
    {
        $quote->loadMissing('costs', 'containerShipments', 'purchaseOrder');
        $quote->load('costs', 'containerShipments', 'purchaseOrder');

        $baseCurrency = $quote->base_currency ?: 'USD';
        $lines = $quote->costs;
        $billableTotal = round($lines->where('is_billable', true)->sum(fn ($line) => $line->amountInBaseCurrency()), 2);
        $freightTotal = round($lines->whereIn('cost_type', [
            'base_freight_cost',
            'fuel_surcharge',
            'pickup_cost',
            'delivery_cost',
            'port_handling_charge',
            'terminal_handling_charge',
            'warehouse_charge',
            'forwarder_margin',
            'supplier_margin',
            'platform_service_fee',
        ])->sum(fn ($line) => $line->amountInBaseCurrency()), 2);
        $insuranceTotal = round($lines->where('cost_type', 'insurance')->sum(fn ($line) => $line->amountInBaseCurrency()), 2);
        $customsTotal = round($lines->whereIn('cost_type', ['customs_clearance_fee', 'customs_duty'])->sum(fn ($line) => $line->amountInBaseCurrency()), 2);
        $taxTotal = round($lines->whereIn('cost_type', ['vat_gst', 'tax'])->sum(fn ($line) => $line->amountInBaseCurrency()), 2);
        $portCharges = round($lines->whereIn('cost_type', ['port_handling_charge', 'terminal_handling_charge'])->sum(fn ($line) => $line->amountInBaseCurrency()), 2);
        $localDelivery = round($lines->whereIn('cost_type', ['pickup_cost', 'delivery_cost'])->sum(fn ($line) => $line->amountInBaseCurrency()), 2);

        $landed = $this->landedCostService->calculate([
            'product_cost' => (float) ($quote->purchaseOrder?->total_amount ?? 0),
            'freight_cost' => $freightTotal,
            'insurance' => $insuranceTotal,
            'duty' => $customsTotal,
            'vat_gst' => $taxTotal,
            'customs_fee' => 0,
            'port_charges' => $portCharges,
            'local_delivery' => $localDelivery,
            'quantity' => (int) ($quote->purchaseOrder?->total_quantity ?? 1),
            'currency' => $baseCurrency,
        ]);

        $quote->update($quote->filterPersistable([
            'freight_cost' => $freightTotal,
            'insurance_cost' => $insuranceTotal,
            'customs_estimate' => $customsTotal,
            'total_cost' => $billableTotal,
            'total_cost_base_currency' => $billableTotal,
            'landed_cost_total' => Arr::get($landed, 'total_landed_cost', 0),
            'base_currency' => $baseCurrency,
        ]));

        foreach ($quote->containerShipments as $shipment) {
            $shipment->update($shipment->filterPersistable([
                'total_freight_cost' => $billableTotal,
                'landed_cost_total' => Arr::get($landed, 'total_landed_cost', 0),
            ]));
        }

        return $landed;
    }

    protected function normalizeLine(B2BFreightQuote $quote, array $data): array
    {
        return [
            'cost_type' => $data['cost_type'],
            'description' => $data['description'] ?? null,
            'amount' => (float) ($data['amount'] ?? 0),
            'currency' => $data['currency'] ?? $quote->currency,
            'exchange_rate_snapshot' => (float) ($data['exchange_rate_snapshot'] ?? 1),
            'payer' => $data['payer'] ?? 'buyer',
            'is_billable' => (bool) ($data['is_billable'] ?? true),
            'is_optional' => (bool) ($data['is_optional'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
