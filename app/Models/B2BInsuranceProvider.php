<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class B2BInsuranceProvider extends Model
{
    protected $table = 'b2b_insurance_providers';

    public const INTEGRATION_MODES = ['manual', 'api'];
    public const INSURANCE_TYPES = [
        'cargo_insurance',
        'marine_cargo_insurance',
        'air_cargo_insurance',
        'road_transport_insurance',
        'rail_cargo_insurance',
        'warehouse_insurance',
        'trade_credit_insurance',
        'supplier_default_insurance',
        'buyer_payment_protection',
        'shipment_delay_insurance',
        'container_insurance',
        'all_risk_insurance',
        'named_perils_insurance',
        'custom_insurance_plan',
    ];

    protected $fillable = [
        'name',
        'company',
        'slug',
        'logo',
        'country',
        'coverage',
        'integration_mode',
        'api_base_url',
        'api_key',
        'api_secret',
        'username',
        'password',
        'credentials',
        'webhook_url',
        'webhook_secret',
        'policy_types',
        'supported_countries',
        'premium_rules',
        'claim_rules',
        'custom_config',
        'is_active',
        'is_default',
        'is_test_mode',
        'successful_requests',
        'failed_requests',
        'last_api_status',
        'last_api_http_status',
        'last_api_response_time_ms',
        'last_api_called_at',
        'last_api_success_at',
        'last_api_failure_at',
        'last_webhook_received_at',
        'webhook_verified_at',
        'notes',
    ];

    protected $casts = [
        'coverage' => 'array',
        'credentials' => 'encrypted:array',
        'policy_types' => 'array',
        'supported_countries' => 'array',
        'premium_rules' => 'array',
        'claim_rules' => 'array',
        'custom_config' => 'array',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'username' => 'encrypted',
        'password' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_test_mode' => 'boolean',
        'last_api_called_at' => 'datetime',
        'last_api_success_at' => 'datetime',
        'last_api_failure_at' => 'datetime',
        'last_webhook_received_at' => 'datetime',
        'webhook_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $provider) {
            if (!$provider->slug) {
                $provider->slug = Str::slug($provider->name . '-' . Str::random(6));
            }
        });
    }

    public function quotes()
    {
        return $this->hasMany(B2BInsuranceQuote::class, 'provider_id');
    }

    public function policies()
    {
        return $this->hasMany(B2BInsurancePolicy::class, 'provider_id');
    }

    public function claims()
    {
        return $this->hasMany(B2BInsuranceClaim::class, 'provider_id');
    }

    public function apiLogs()
    {
        return $this->hasMany(B2BInsuranceApiLog::class, 'provider_id');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
