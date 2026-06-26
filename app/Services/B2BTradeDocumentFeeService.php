<?php

namespace App\Services;

use App\Models\B2BTradeDocument;

class B2BTradeDocumentFeeService
{
    public const CHARGE_TYPES = ['fixed', 'percentage'];

    public const CHARGEABLE_DOCUMENT_TYPES = [
        'commercial_invoice',
        'packing_list',
        'certificate_of_origin',
        'bill_of_lading',
    ];

    public function settings(): array
    {
        $type = (string) get_setting('b2b_trade_document_fee_type', 'fixed');
        if (!in_array($type, self::CHARGE_TYPES, true)) {
            $type = 'fixed';
        }

        return [
            'enabled' => (bool) get_setting('b2b_trade_document_fee_enabled', 0),
            'type' => $type,
            'percent' => (float) get_setting('b2b_trade_document_fee_percent', 0),
            'fixed' => (float) get_setting('b2b_trade_document_fee_fixed', 0),
        ];
    }

    public function isChargeableDocumentType(string $documentType): bool
    {
        return in_array($documentType, self::CHARGEABLE_DOCUMENT_TYPES, true);
    }

    public function calculate(string $documentType, float $baseAmount = 0): array
    {
        $settings = $this->settings();
        $serviceFeeAmount = 0.0;
        $baseAmount = round(max($baseAmount, 0), 2);

        if ($settings['enabled'] && $this->isChargeableDocumentType($documentType)) {
            $serviceFeeAmount = $settings['type'] === 'percentage'
                ? round($baseAmount * ($settings['percent'] / 100), 2)
                : round($settings['fixed'], 2);
        }

        return [
            'enabled' => $settings['enabled'],
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fixed' => $settings['fixed'],
            'document_type' => $documentType,
            'base_amount' => $baseAmount,
            'service_fee_amount' => $serviceFeeAmount,
        ];
    }

    public function applyToPayload(array $payload): array
    {
        $documentType = (string) ($payload['document_type'] ?? '');
        $charge = $this->calculate($documentType, (float) ($payload['fee_base_amount'] ?? 0));

        return array_merge($payload, [
            'service_fee_fixed_snapshot' => $charge['service_fee_amount'] > 0 ? $charge['fixed'] : 0,
            'service_fee_amount' => $charge['service_fee_amount'],
        ]);
    }

    public function chargeableDocumentsCount(): int
    {
        return B2BTradeDocument::whereIn('document_type', self::CHARGEABLE_DOCUMENT_TYPES)->count();
    }

    public function totalDocumentsCount(): int
    {
        return B2BTradeDocument::count();
    }

    public function platformRevenue(): float
    {
        return round(
            (float) B2BTradeDocument::whereIn('document_type', self::CHARGEABLE_DOCUMENT_TYPES)->sum('service_fee_amount'),
            2
        );
    }

    public function revenueProjection(int $documentCount): array
    {
        $settings = $this->settings();
        $unitFee = $settings['type'] === 'percentage'
            ? round((float) B2BTradeDocument::whereIn('document_type', self::CHARGEABLE_DOCUMENT_TYPES)->avg('service_fee_amount'), 2)
            : $settings['fixed'];

        return [
            'type' => $settings['type'],
            'percent' => $settings['percent'],
            'fee' => $settings['fixed'],
            'unit_fee' => $unitFee,
            'document_count' => $documentCount,
            'projected_revenue' => round($unitFee * $documentCount, 2),
        ];
    }
}
