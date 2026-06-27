<?php

namespace App\Services;

use App\Models\AIProviderSetting;
use App\Models\B2BCompany;
use App\Models\B2BContainerShipment;
use App\Models\B2BFreightQuote;
use App\Models\B2BInsuranceApiLog;
use App\Models\B2BInsuranceClaim;
use App\Models\B2BInsuranceClaimDocument;
use App\Models\B2BInsuranceEvent;
use App\Models\B2BInsurancePayment;
use App\Models\B2BInsurancePolicy;
use App\Models\B2BInsuranceProvider;
use App\Models\B2BInsuranceQuote;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BShipment;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class B2BInsuranceService
{
    public const CLAIM_DOCUMENT_TYPES = [
        'invoice',
        'packing_list',
        'bill_of_lading',
        'air_waybill',
        'photos',
        'damage_report',
        'survey_report',
        'inspection_report',
        'custom_document',
    ];

    public function __construct(
        protected CurrencyService $currencyService,
        protected B2BAuditService $auditService,
        protected B2BNotificationService $notificationService,
        protected ?AIManager $aiManager = null,
        protected ?AIPromptService $aiPromptService = null,
        protected ?AIRequestService $aiRequestService = null
    ) {
    }

    public function providerOptions(): array
    {
        return [
            'integration_modes' => B2BInsuranceProvider::INTEGRATION_MODES,
            'insurance_types' => B2BInsuranceProvider::INSURANCE_TYPES,
            'claim_document_types' => self::CLAIM_DOCUMENT_TYPES,
        ];
    }

    public function createProvider(array $data, ?int $actorUserId = null): B2BInsuranceProvider
    {
        return DB::transaction(function () use ($data, $actorUserId) {
            if (!empty($data['is_default'])) {
                B2BInsuranceProvider::query()->update(['is_default' => false]);
            }

            $provider = B2BInsuranceProvider::create((new B2BInsuranceProvider())->filterPersistable([
                'name' => $data['name'],
                'company' => $data['company'] ?? $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'logo' => $data['logo'] ?? null,
                'country' => $data['country'] ?? null,
                'coverage' => $data['coverage'] ?? [],
                'integration_mode' => $data['integration_mode'] ?? 'manual',
                'api_base_url' => $data['api_base_url'] ?? null,
                'api_key' => $data['api_key'] ?? null,
                'api_secret' => $data['api_secret'] ?? null,
                'username' => $data['username'] ?? null,
                'password' => $data['password'] ?? null,
                'credentials' => $data['credentials'] ?? [],
                'webhook_url' => $data['webhook_url'] ?? null,
                'webhook_secret' => $data['webhook_secret'] ?? null,
                'policy_types' => $data['policy_types'] ?? [],
                'supported_countries' => $data['supported_countries'] ?? [],
                'premium_rules' => $data['premium_rules'] ?? [],
                'claim_rules' => $data['claim_rules'] ?? [],
                'custom_config' => $data['custom_config'] ?? [],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_test_mode' => (bool) ($data['is_test_mode'] ?? true),
                'notes' => $data['notes'] ?? null,
            ]));

            $this->auditService->log($actorUserId, null, 'insurance_provider_created', $provider, 'Insurance provider created.');

            return $provider->fresh();
        });
    }

    public function updateProvider(B2BInsuranceProvider $provider, array $data, ?int $actorUserId = null): B2BInsuranceProvider
    {
        return DB::transaction(function () use ($provider, $data, $actorUserId) {
            if (!empty($data['is_default'])) {
                B2BInsuranceProvider::query()->whereKeyNot($provider->id)->update(['is_default' => false]);
            }

            $provider->update($provider->filterPersistable(array_filter([
                'name' => $data['name'] ?? $provider->name,
                'company' => $data['company'] ?? $provider->company,
                'slug' => $data['slug'] ?? $provider->slug,
                'logo' => $data['logo'] ?? $provider->logo,
                'country' => $data['country'] ?? $provider->country,
                'coverage' => $data['coverage'] ?? $provider->coverage,
                'integration_mode' => $data['integration_mode'] ?? $provider->integration_mode,
                'api_base_url' => $data['api_base_url'] ?? $provider->api_base_url,
                'api_key' => $data['api_key'] ?? null,
                'api_secret' => $data['api_secret'] ?? null,
                'username' => $data['username'] ?? null,
                'password' => $data['password'] ?? null,
                'credentials' => $data['credentials'] ?? $provider->credentials,
                'webhook_url' => $data['webhook_url'] ?? $provider->webhook_url,
                'webhook_secret' => $data['webhook_secret'] ?? null,
                'policy_types' => $data['policy_types'] ?? $provider->policy_types,
                'supported_countries' => $data['supported_countries'] ?? $provider->supported_countries,
                'premium_rules' => $data['premium_rules'] ?? $provider->premium_rules,
                'claim_rules' => $data['claim_rules'] ?? $provider->claim_rules,
                'custom_config' => $data['custom_config'] ?? $provider->custom_config,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $provider->is_active,
                'is_default' => array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $provider->is_default,
                'is_test_mode' => array_key_exists('is_test_mode', $data) ? (bool) $data['is_test_mode'] : $provider->is_test_mode,
                'notes' => $data['notes'] ?? $provider->notes,
            ], static fn ($value) => $value !== null)));

            $this->auditService->log($actorUserId, null, 'insurance_provider_updated', $provider, 'Insurance provider updated.');

            return $provider->fresh();
        });
    }

    public function generateQuote(array $data, ?User $actor = null, ?B2BCompany $company = null): B2BInsuranceQuote
    {
        return DB::transaction(function () use ($data, $actor, $company) {
            $provider = $this->resolveProvider($data['provider_id'] ?? null);
            $currency = (string) ($data['currency'] ?? 'USD');
            $risk = $this->analyzeRisk($data, $actor, $company);
            $breakdown = $this->calculatePremiumBreakdown($data, $risk, $provider);

            $quote = B2BInsuranceQuote::create((new B2BInsuranceQuote())->filterPersistable([
                'quote_number' => $this->nextNumber('IQ'),
                'provider_id' => $provider?->id,
                'buyer_company_id' => $data['buyer_company_id'] ?? $company?->id,
                'supplier_company_id' => $data['supplier_company_id'] ?? null,
                'created_by' => $actor?->id,
                'shipment_id' => $data['shipment_id'] ?? null,
                'container_shipment_id' => $data['container_shipment_id'] ?? null,
                'freight_quote_id' => $data['freight_quote_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'proforma_invoice_id' => $data['proforma_invoice_id'] ?? null,
                'insurance_type' => $data['insurance_type'],
                'transport_mode' => $data['transport_mode'] ?? null,
                'incoterm' => $data['incoterm'] ?? null,
                'container_type' => $data['container_type'] ?? null,
                'origin_country' => $data['origin_country'] ?? null,
                'destination_country' => $data['destination_country'] ?? null,
                'origin_port' => $data['origin_port'] ?? null,
                'destination_port' => $data['destination_port'] ?? null,
                'commodity' => $data['commodity'] ?? null,
                'hs_code' => $data['hs_code'] ?? null,
                'weight' => (float) ($data['weight'] ?? 0),
                'volume' => (float) ($data['volume'] ?? 0),
                'shipment_value' => (float) ($data['shipment_value'] ?? 0),
                'coverage_amount' => (float) ($data['coverage_amount'] ?? ($data['shipment_value'] ?? 0)),
                'currency' => $currency,
                'exchange_rate_snapshot' => $this->currencyService->rateFor($currency),
                'currency_snapshot' => $this->currencyService->snapshot($currency),
                'risk_score' => $risk['score'],
                'risk_breakdown' => $risk,
                'premium' => $breakdown['premium'],
                'tax_amount' => $breakdown['tax_amount'],
                'additional_charges' => $breakdown['additional_charges'],
                'platform_fee' => $breakdown['platform_fee'],
                'discount_amount' => $breakdown['discount_amount'],
                'final_amount' => $breakdown['final_amount'],
                'premium_breakdown' => $breakdown,
                'calculation_history' => [$breakdown],
                'request_payload' => $data,
                'response_payload' => [
                    'deterministic' => true,
                    'provider_id' => $provider?->id,
                ],
                'ai_recommendation' => $risk['ai_recommendation'] ?? null,
                'status' => 'quoted',
                'expires_at' => now()->addDays(7),
            ]));

            $this->recordEvent($quote, 'quote_generated', 'Insurance quote generated', 'Trade insurance quote generated.', $company?->id, $actor?->id, $quote->request_payload);
            $this->auditService->log($actor?->id, $company?->id, 'insurance_quote_generated', $quote, 'Insurance quote generated.', ['quote_number' => $quote->quote_number]);
            $this->notificationService->notifyInsuranceQuoteGenerated($quote);

            return $quote->fresh(['provider']);
        });
    }

    public function recalculateQuote(B2BInsuranceQuote $quote, ?User $actor = null, ?B2BCompany $company = null): B2BInsuranceQuote
    {
        $provider = $this->resolveProvider($quote->provider_id);
        $requestPayload = array_merge($quote->request_payload ?? [], $quote->only([
            'insurance_type',
            'transport_mode',
            'incoterm',
            'container_type',
            'origin_country',
            'destination_country',
            'origin_port',
            'destination_port',
            'commodity',
            'hs_code',
            'weight',
            'volume',
            'shipment_value',
            'coverage_amount',
            'currency',
        ]));

        $risk = $this->analyzeRisk($requestPayload, $actor ?: $quote->creator, $company ?: $quote->buyerCompany ?: $quote->supplierCompany);
        $breakdown = $this->calculatePremiumBreakdown($requestPayload, $risk, $provider);
        $history = collect($quote->calculation_history ?? [])->push($breakdown)->values()->all();

        $quote->update([
            'risk_score' => $risk['score'],
            'risk_breakdown' => $risk,
            'premium' => $breakdown['premium'],
            'tax_amount' => $breakdown['tax_amount'],
            'additional_charges' => $breakdown['additional_charges'],
            'platform_fee' => $breakdown['platform_fee'],
            'discount_amount' => $breakdown['discount_amount'],
            'final_amount' => $breakdown['final_amount'],
            'premium_breakdown' => $breakdown,
            'calculation_history' => $history,
            'ai_recommendation' => $risk['ai_recommendation'] ?? $quote->ai_recommendation,
        ]);

        return $quote->fresh();
    }

    public function issuePolicy(B2BInsuranceQuote $quote, array $data, ?User $actor = null, ?int $actorCompanyId = null): B2BInsurancePolicy
    {
        return DB::transaction(function () use ($quote, $data, $actor, $actorCompanyId) {
            $policy = B2BInsurancePolicy::create((new B2BInsurancePolicy())->filterPersistable([
                'policy_number' => $this->nextNumber('IP'),
                'provider_id' => $quote->provider_id,
                'quote_id' => $quote->id,
                'buyer_company_id' => $quote->buyer_company_id,
                'supplier_company_id' => $quote->supplier_company_id,
                'policy_holder_user_id' => $data['policy_holder_user_id'] ?? $actor?->id,
                'issued_by' => $actor?->id,
                'shipment_id' => $quote->shipment_id,
                'container_shipment_id' => $quote->container_shipment_id,
                'freight_quote_id' => $quote->freight_quote_id,
                'purchase_order_id' => $quote->purchase_order_id,
                'proforma_invoice_id' => $quote->proforma_invoice_id,
                'finance_reference_type' => $data['finance_reference_type'] ?? null,
                'finance_reference_id' => $data['finance_reference_id'] ?? null,
                'insurance_type' => $quote->insurance_type,
                'transport_mode' => $quote->transport_mode,
                'coverage_plan' => $data['coverage_plan'] ?? 'standard',
                'status' => $data['status'] ?? 'approved',
                'coverage_amount' => $quote->coverage_amount,
                'premium' => $quote->premium,
                'tax_amount' => $quote->tax_amount,
                'deductible_amount' => (float) ($data['deductible_amount'] ?? 0),
                'insured_value' => $quote->shipment_value,
                'currency' => $quote->currency,
                'exchange_rate_snapshot' => $quote->exchange_rate_snapshot,
                'currency_snapshot' => $quote->currency_snapshot,
                'coverage_details' => $data['coverage_details'] ?? [
                    'insurance_type' => $quote->insurance_type,
                    'origin_country' => $quote->origin_country,
                    'destination_country' => $quote->destination_country,
                ],
                'premium_breakdown' => $quote->premium_breakdown,
                'attachment_paths' => $data['attachment_paths'] ?? [],
                'metadata' => $data['metadata'] ?? [],
                'coverage_start' => $data['coverage_start'] ?? now()->toDateString(),
                'coverage_end' => $data['coverage_end'] ?? now()->addDays(30)->toDateString(),
                'issued_at' => now(),
                'activated_at' => ($data['status'] ?? 'approved') === 'active' ? now() : null,
            ]));

            $quote->update(['status' => 'accepted']);

            $this->recordEvent($policy, 'policy_issued', 'Policy issued', 'Trade insurance policy issued.', $actorCompanyId, $actor?->id, ['quote_id' => $quote->id]);
            $this->auditService->log($actor?->id, $actorCompanyId, 'insurance_policy_issued', $policy, 'Insurance policy issued.', ['policy_number' => $policy->policy_number]);
            $this->notificationService->notifyInsurancePolicyIssued($policy);

            return $policy->fresh(['provider', 'quote']);
        });
    }

    public function submitClaim(B2BInsurancePolicy $policy, array $data, ?User $actor = null, ?int $actorCompanyId = null): B2BInsuranceClaim
    {
        return DB::transaction(function () use ($policy, $data, $actor, $actorCompanyId) {
            $claim = B2BInsuranceClaim::create((new B2BInsuranceClaim())->filterPersistable([
                'claim_number' => $this->nextNumber('IC'),
                'policy_id' => $policy->id,
                'provider_id' => $policy->provider_id,
                'buyer_company_id' => $policy->buyer_company_id,
                'supplier_company_id' => $policy->supplier_company_id,
                'claimant_user_id' => $actor?->id,
                'claimant_company_id' => $actorCompanyId,
                'shipment_id' => $policy->shipment_id,
                'container_shipment_id' => $policy->container_shipment_id,
                'freight_quote_id' => $policy->freight_quote_id,
                'purchase_order_id' => $policy->purchase_order_id,
                'proforma_invoice_id' => $policy->proforma_invoice_id,
                'status' => 'submitted',
                'claim_type' => $data['claim_type'] ?? 'damage',
                'incident_country' => $data['incident_country'] ?? null,
                'incident_location' => $data['incident_location'] ?? null,
                'incident_reference' => $data['incident_reference'] ?? null,
                'summary' => $data['summary'],
                'description' => $data['description'] ?? null,
                'claim_amount' => (float) ($data['claim_amount'] ?? 0),
                'approved_amount' => 0,
                'settled_amount' => 0,
                'currency' => $data['currency'] ?? $policy->currency,
                'evidence' => $data['evidence'] ?? [],
                'timeline' => [[
                    'status' => 'submitted',
                    'at' => now()->toIso8601String(),
                    'by' => $actor?->id,
                ]],
                'comments' => [],
                'incident_at' => $data['incident_at'] ?? now(),
                'submitted_at' => now(),
            ]));

            foreach (($data['documents'] ?? []) as $document) {
                $this->attachClaimDocument($claim, $document, $actor);
            }

            $analysis = $this->validateClaim($claim, $actor, $actorCompanyId);
            $claim->update([
                'validation_summary' => $analysis['validation_summary'],
                'fraud_signals' => $analysis['fraud_signals'],
            ]);

            $policy->update(['status' => 'claim_submitted']);
            $this->recordEvent($claim, 'claim_submitted', 'Claim submitted', 'Insurance claim submitted.', $actorCompanyId, $actor?->id, ['policy_id' => $policy->id]);
            $this->auditService->log($actor?->id, $actorCompanyId, 'insurance_claim_submitted', $claim, 'Insurance claim submitted.', ['claim_number' => $claim->claim_number]);
            $this->notificationService->notifyInsuranceClaimSubmitted($claim);

            return $claim->fresh(['documents', 'policy']);
        });
    }

    public function attachClaimDocument(B2BInsuranceClaim $claim, array $data, ?User $actor = null): B2BInsuranceClaimDocument
    {
        $document = B2BInsuranceClaimDocument::create((new B2BInsuranceClaimDocument())->filterPersistable([
            'claim_id' => $claim->id,
            'uploaded_by' => $actor?->id,
            'document_type' => $data['document_type'],
            'title' => $data['title'] ?? Str::title(str_replace('_', ' ', $data['document_type'])),
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'] ?? null,
            'file_size' => $data['file_size'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]));

        $this->recordEvent($claim, 'claim_document_added', 'Claim document uploaded', 'Insurance claim document uploaded.', $claim->claimant_company_id, $actor?->id, ['document_id' => $document->id]);

        return $document;
    }

    public function transitionClaim(B2BInsuranceClaim $claim, string $status, array $data = [], ?User $actor = null, ?int $actorCompanyId = null): B2BInsuranceClaim
    {
        return DB::transaction(function () use ($claim, $status, $data, $actor, $actorCompanyId) {
            $timeline = collect($claim->timeline ?? [])->push([
                'status' => $status,
                'at' => now()->toIso8601String(),
                'by' => $actor?->id,
                'notes' => $data['notes'] ?? null,
            ])->values()->all();

            $comments = collect($claim->comments ?? []);
            if (!empty($data['comment'])) {
                $comments->push([
                    'message' => $data['comment'],
                    'user_id' => $actor?->id,
                    'company_id' => $actorCompanyId,
                    'created_at' => now()->toIso8601String(),
                ]);
            }

            $updates = [
                'status' => $status,
                'timeline' => $timeline,
                'comments' => $comments->values()->all(),
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'approved_amount' => array_key_exists('approved_amount', $data) ? (float) $data['approved_amount'] : $claim->approved_amount,
                'settled_amount' => array_key_exists('settled_amount', $data) ? (float) $data['settled_amount'] : $claim->settled_amount,
                'resolution_data' => $data['resolution_data'] ?? $claim->resolution_data,
                'approved_at' => $status === 'approved' ? now() : $claim->approved_at,
                'rejected_at' => $status === 'rejected' ? now() : $claim->rejected_at,
                'settled_at' => in_array($status, ['settled', 'partial_settlement'], true) ? now() : $claim->settled_at,
                'appealed_at' => $status === 'appealed' ? now() : $claim->appealed_at,
            ];

            $claim->update($updates);

            if (in_array($status, ['settled', 'partial_settlement'], true)) {
                $claim->policy?->update(['status' => 'settled']);
            }

            $this->recordEvent($claim, 'claim_status_updated', 'Claim status updated', 'Insurance claim status updated.', $actorCompanyId, $actor?->id, ['status' => $status]);
            $this->auditService->log($actor?->id, $actorCompanyId, 'insurance_claim_status_updated', $claim, 'Insurance claim status updated.', ['status' => $status]);
            $this->notificationService->notifyInsuranceClaimStatusUpdated($claim, $status);

            return $claim->fresh(['policy']);
        });
    }

    public function recordPayment(array $data, ?User $actor = null, ?int $actorCompanyId = null): B2BInsurancePayment
    {
        return DB::transaction(function () use ($data, $actor, $actorCompanyId) {
            $payment = B2BInsurancePayment::create((new B2BInsurancePayment())->filterPersistable([
                'policy_id' => $data['policy_id'] ?? null,
                'claim_id' => $data['claim_id'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'buyer_company_id' => $data['buyer_company_id'] ?? null,
                'supplier_company_id' => $data['supplier_company_id'] ?? null,
                'recorded_by' => $actor?->id,
                'payment_type' => $data['payment_type'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'amount' => (float) ($data['amount'] ?? 0),
                'tax_amount' => (float) ($data['tax_amount'] ?? 0),
                'fees' => (float) ($data['fees'] ?? 0),
                'currency' => $data['currency'] ?? 'USD',
                'status' => $data['status'] ?? 'paid',
                'meta' => $data['meta'] ?? [],
                'paid_at' => $data['paid_at'] ?? now(),
            ]));

            $model = $payment->claim ?: $payment->policy;
            if ($model) {
                $this->recordEvent($model, 'payment_recorded', 'Insurance payment recorded', 'Insurance payment recorded.', $actorCompanyId, $actor?->id, ['payment_id' => $payment->id]);
                $this->auditService->log($actor?->id, $actorCompanyId, 'insurance_payment_recorded', $payment, 'Insurance payment recorded.', ['payment_type' => $payment->payment_type]);
            }

            return $payment->fresh();
        });
    }

    public function syncPoliciesForShipment(B2BShipment $shipment): int
    {
        $status = match ($shipment->status) {
            'preparing', 'label_created', 'pickup_scheduled' => 'approved',
            'picked_up', 'export_customs', 'in_transit', 'import_customs', 'out_for_delivery', 'delayed', 'customs_hold' => 'in_transit',
            'delivered' => 'active',
            'cancelled', 'returned' => 'cancelled',
            default => null,
        };

        if (!$status) {
            return 0;
        }

        $updated = 0;

        B2BInsurancePolicy::query()
            ->where('shipment_id', $shipment->id)
            ->whereIn('status', ['approved', 'active', 'in_transit'])
            ->get()
            ->each(function (B2BInsurancePolicy $policy) use ($status, &$updated, $shipment) {
                if ($policy->status === $status) {
                    return;
                }

                $policy->update([
                    'status' => $status,
                    'activated_at' => $status === 'in_transit' ? ($policy->activated_at ?: now()) : $policy->activated_at,
                    'cancelled_at' => $status === 'cancelled' ? now() : $policy->cancelled_at,
                ]);

                $this->recordEvent($policy, 'shipment_status_synced', 'Shipment-linked policy updated', 'Shipment-linked policy status synchronized.', $policy->buyer_company_id ?: $policy->supplier_company_id, null, ['shipment_status' => $shipment->status]);
                $updated++;
            });

        return $updated;
    }

    public function processLifecycle(): array
    {
        $expiredPolicies = B2BInsurancePolicy::query()
            ->whereNotNull('coverage_end')
            ->whereDate('coverage_end', '<', now()->toDateString())
            ->whereNotIn('status', ['expired', 'cancelled', 'settled'])
            ->get();

        $expiringSoon = B2BInsurancePolicy::query()
            ->whereNotNull('coverage_end')
            ->whereDate('coverage_end', now()->toDateString())
            ->orWhereDate('coverage_end', now()->addDays(3)->toDateString())
            ->get();

        foreach ($expiredPolicies as $policy) {
            $policy->update([
                'status' => 'expired',
                'expired_at' => $policy->expired_at ?: now(),
            ]);

            $this->notificationService->notifyInsurancePolicyExpiring($policy, true);
        }

        foreach ($expiringSoon as $policy) {
            if (!in_array($policy->status, ['expired', 'cancelled'], true)) {
                $this->notificationService->notifyInsurancePolicyExpiring($policy, false);
            }
        }

        return [
            'expired' => $expiredPolicies->count(),
            'expiring_soon' => $expiringSoon->count(),
        ];
    }

    public function adminDashboard(): array
    {
        return [
            'providers' => B2BInsuranceProvider::query()->count(),
            'active_providers' => B2BInsuranceProvider::query()->where('is_active', true)->count(),
            'quotes' => B2BInsuranceQuote::query()->count(),
            'policies' => B2BInsurancePolicy::query()->count(),
            'active_policies' => B2BInsurancePolicy::query()->whereIn('status', ['approved', 'active', 'in_transit', 'claim_submitted'])->count(),
            'expired_policies' => B2BInsurancePolicy::query()->where('status', 'expired')->count(),
            'claims' => B2BInsuranceClaim::query()->count(),
            'pending_claims' => B2BInsuranceClaim::query()->whereIn('status', ['submitted', 'review', 'investigation', 'appealed'])->count(),
            'revenue' => (float) B2BInsurancePayment::query()->where('payment_type', 'premium')->where('status', 'paid')->sum('amount'),
            'settlements' => (float) B2BInsurancePayment::query()->where('payment_type', 'claim_settlement')->where('status', 'paid')->sum('amount'),
        ];
    }

    public function companyDashboard(B2BCompany $company, string $role = 'buyer'): array
    {
        $companyColumn = $role === 'supplier' ? 'supplier_company_id' : 'buyer_company_id';

        return [
            'policies' => B2BInsurancePolicy::query()->where($companyColumn, $company->id)->count(),
            'active_policies' => B2BInsurancePolicy::query()->where($companyColumn, $company->id)->whereIn('status', ['approved', 'active', 'in_transit', 'claim_submitted'])->count(),
            'claims' => B2BInsuranceClaim::query()->where($companyColumn, $company->id)->count(),
            'open_claims' => B2BInsuranceClaim::query()->where($companyColumn, $company->id)->whereIn('status', ['submitted', 'review', 'investigation', 'appealed'])->count(),
            'coverage' => (float) B2BInsurancePolicy::query()->where($companyColumn, $company->id)->sum('coverage_amount'),
            'premium' => (float) B2BInsurancePolicy::query()->where($companyColumn, $company->id)->sum('premium'),
        ];
    }

    public function validateClaim(B2BInsuranceClaim $claim, ?User $actor = null, ?int $actorCompanyId = null): array
    {
        $documentTypes = $claim->documents()->pluck('document_type')->all();
        $required = ['invoice', 'damage_report'];
        $missing = array_values(array_diff($required, $documentTypes));
        $coverageGap = max(0, (float) $claim->claim_amount - (float) $claim->policy?->coverage_amount);
        $fraudSignals = [];

        if ($claim->incident_at && $claim->policy?->coverage_start && $claim->incident_at->lt($claim->policy->coverage_start)) {
            $fraudSignals[] = 'incident_before_coverage_start';
        }

        if ($claim->claim_amount > (($claim->policy?->coverage_amount ?? 0) * 1.10)) {
            $fraudSignals[] = 'claim_amount_exceeds_coverage';
        }

        if ($missing !== []) {
            $fraudSignals[] = 'missing_required_documents';
        }

        $result = [
            'validation_summary' => [
                'missing_documents' => $missing,
                'coverage_gap' => round($coverageGap, 2),
                'document_count' => count($documentTypes),
                'within_coverage' => $coverageGap <= 0,
            ],
            'fraud_signals' => [
                'signals' => $fraudSignals,
                'score' => min(100, count($fraudSignals) * 25),
            ],
        ];

        $aiResult = $this->runAiModule('b2b_insurance_claim_validation', ['claim_json' => $claim->load('documents', 'policy')->toArray()], $actor, $actorCompanyId);
        if ($aiResult) {
            $result['validation_summary']['ai'] = $aiResult;
        }

        $fraudAi = $this->runAiModule('b2b_insurance_fraud_detection', ['claim_json' => $claim->load('documents', 'policy')->toArray()], $actor, $actorCompanyId);
        if ($fraudAi) {
            $result['fraud_signals']['ai'] = $fraudAi;
        }

        return $result;
    }

    public function logProviderCall(B2BInsuranceProvider $provider, Model $loggable, array $payload): B2BInsuranceApiLog
    {
        return B2BInsuranceApiLog::create([
            'provider_id' => $provider->id,
            'loggable_type' => $loggable::class,
            'loggable_id' => $loggable->getKey(),
            'direction' => $payload['direction'] ?? 'outbound',
            'endpoint' => $payload['endpoint'] ?? null,
            'request_method' => $payload['request_method'] ?? null,
            'http_status' => $payload['http_status'] ?? null,
            'status' => $payload['status'] ?? 'pending',
            'latency_ms' => $payload['latency_ms'] ?? null,
            'request_payload' => $payload['request_payload'] ?? null,
            'response_payload' => $payload['response_payload'] ?? null,
            'error_message' => $payload['error_message'] ?? null,
        ]);
    }

    protected function analyzeRisk(array $data, ?User $actor = null, ?B2BCompany $company = null): array
    {
        $score = 25.0;
        $factors = [];

        $transportMode = (string) ($data['transport_mode'] ?? '');
        $insuranceType = (string) ($data['insurance_type'] ?? '');
        $shipmentValue = (float) ($data['shipment_value'] ?? 0);
        $weight = (float) ($data['weight'] ?? 0);
        $destination = strtolower((string) ($data['destination_country'] ?? ''));
        $commodity = strtolower((string) ($data['commodity'] ?? ''));
        $incoterm = strtoupper((string) ($data['incoterm'] ?? ''));

        $modeRisk = [
            'sea_freight' => 12,
            'air_freight' => 9,
            'road' => 14,
            'rail' => 8,
            'warehouse' => 7,
        ];
        $score += $modeRisk[$transportMode] ?? 10;
        $factors['transport_mode'] = $modeRisk[$transportMode] ?? 10;

        if (str_contains($insuranceType, 'all_risk')) {
            $score += 8;
            $factors['coverage_scope'] = 8;
        }

        if ($shipmentValue >= 100000) {
            $score += 14;
            $factors['shipment_value'] = 14;
        } elseif ($shipmentValue >= 25000) {
            $score += 8;
            $factors['shipment_value'] = 8;
        }

        if ($weight >= 1000) {
            $score += 6;
            $factors['weight'] = 6;
        }

        if (in_array($destination, ['nigeria', 'brazil', 'south africa'], true)) {
            $score += 10;
            $factors['destination_country'] = 10;
        }

        if (str_contains($commodity, 'electronics') || str_contains($commodity, 'pharmaceutical')) {
            $score += 12;
            $factors['commodity'] = 12;
        } elseif (str_contains($commodity, 'machinery')) {
            $score += 7;
            $factors['commodity'] = 7;
        }

        if (in_array($incoterm, ['EXW', 'DDP'], true)) {
            $score += 6;
            $factors['incoterm'] = 6;
        }

        $score = min(100, round($score, 2));
        $result = [
            'score' => $score,
            'risk_level' => $score >= 70 ? 'high' : ($score >= 45 ? 'medium' : 'low'),
            'factors' => $factors,
            'generated_at' => now()->toIso8601String(),
            'source' => 'deterministic',
        ];

        $aiResult = $this->runAiModule('b2b_insurance_risk', ['quote_json' => $data], $actor, $company?->id);
        if ($aiResult) {
            $result['ai_recommendation'] = $aiResult;
        }

        return $result;
    }

    protected function calculatePremiumBreakdown(array $data, array $risk, ?B2BInsuranceProvider $provider = null): array
    {
        $coverageAmount = (float) ($data['coverage_amount'] ?? $data['shipment_value'] ?? 0);
        $baseRate = match ($data['insurance_type']) {
            'trade_credit_insurance', 'buyer_payment_protection' => 0.018,
            'supplier_default_insurance' => 0.021,
            'warehouse_insurance' => 0.009,
            'shipment_delay_insurance' => 0.011,
            'all_risk_insurance' => 0.014,
            default => 0.012,
        };

        $riskMultiplier = 1 + (((float) $risk['score'] - 25) / 100);
        $premium = round($coverageAmount * $baseRate * $riskMultiplier, 2);
        $taxAmount = round($premium * 0.10, 2);
        $additionalCharges = round($coverageAmount * 0.0025, 2);
        $platformFee = round(max(5, $premium * 0.03), 2);
        $discountAmount = round(($provider?->is_default ? $premium * 0.03 : 0), 2);
        $finalAmount = round($premium + $taxAmount + $additionalCharges + $platformFee - $discountAmount, 2);

        return [
            'base_rate' => $baseRate,
            'risk_multiplier' => round($riskMultiplier, 4),
            'premium' => $premium,
            'tax_amount' => $taxAmount,
            'additional_charges' => $additionalCharges,
            'platform_fee' => $platformFee,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    protected function recordEvent(Model $model, string $eventType, string $title, ?string $description = null, ?int $companyId = null, ?int $userId = null, ?array $payload = null): B2BInsuranceEvent
    {
        return $model->events()->create([
            'provider_id' => $model->provider_id ?? null,
            'company_id' => $companyId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    protected function resolveProvider(?int $providerId): ?B2BInsuranceProvider
    {
        if ($providerId) {
            return B2BInsuranceProvider::query()->where('is_active', true)->find($providerId);
        }

        return B2BInsuranceProvider::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    protected function nextNumber(string $prefix): string
    {
        return $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    protected function runAiModule(string $module, array $variables, ?User $actor = null, ?int $companyId = null): ?array
    {
        if (
            !$this->aiPromptService
            || !$this->aiRequestService
            || !Schema::hasTable('ai_prompt_templates')
            || !Schema::hasTable('ai_provider_settings')
            || !AIProviderSetting::query()->where('is_active', true)->exists()
        ) {
            return null;
        }

        try {
            $rendered = $this->aiPromptService->render($module, $variables);
            $response = $this->aiRequestService->request([
                'user' => $actor,
                'company_id' => $companyId,
                'module' => $module,
                'system_prompt' => $rendered['system_prompt'],
                'prompt' => $rendered['user_prompt'],
                'max_tokens' => 500,
                'temperature' => 0.2,
                'metadata' => ['variables' => Arr::only($variables, array_keys($variables))],
            ]);

            return [
                'provider' => $response['provider'] ?? null,
                'model' => $response['model'] ?? null,
                'content' => $response['content'] ?? null,
                'request_id' => $response['request_id'] ?? null,
            ];
        } catch (\Throwable $throwable) {
            return [
                'error' => Str::limit($throwable->getMessage(), 200),
                'fallback' => true,
            ];
        }
    }
}
