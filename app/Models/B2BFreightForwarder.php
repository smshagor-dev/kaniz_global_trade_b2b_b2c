<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BFreightForwarder extends Model
{
    public const DRIVERS = [
        'maersk',
        'msc',
        'cma_cgm',
        'hapag_lloyd',
        'cosco',
        'evergreen',
        'one',
        'dp_world',
        'freightos',
        'flexport',
        'custom',
    ];

    protected $table = 'b2b_freight_forwarders';

    protected $fillable = [
        'name',
        'driver',
        'logo',
        'banner',
        'website',
        'contact_email',
        'contact_phone',
        'support_email',
        'support_phone',
        'provider_type',
        'api_base_url',
        'api_key',
        'api_secret',
        'username',
        'password',
        'account_number',
        'oauth_token',
        'refresh_token',
        'webhook_secret',
        'environment',
        'is_test_mode',
        'is_active',
        'supported_modes',
        'supported_services',
        'supported_countries',
        'container_types',
        'default_freight_cost',
        'default_insurance_cost',
        'default_customs_estimate',
        'credentials',
        'custom_config',
        'integration_events',
        'last_api_test_status',
        'last_api_test_message',
        'last_api_tested_at',
        'last_api_status',
        'last_api_http_status',
        'last_api_response_time_ms',
        'last_api_called_at',
        'last_api_success_at',
        'last_api_failure_at',
        'last_webhook_received_at',
        'webhook_verified_at',
        'last_sync_at',
        'successful_requests',
        'failed_requests',
        'average_response_time_ms',
        'notes',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'username' => 'encrypted',
        'password' => 'encrypted',
        'account_number' => 'encrypted',
        'oauth_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'supported_modes' => 'array',
        'supported_services' => 'array',
        'supported_countries' => 'array',
        'container_types' => 'array',
        'default_freight_cost' => 'decimal:2',
        'default_insurance_cost' => 'decimal:2',
        'default_customs_estimate' => 'decimal:2',
        'credentials' => 'array',
        'custom_config' => 'array',
        'integration_events' => 'array',
        'is_test_mode' => 'boolean',
        'is_active' => 'boolean',
        'last_api_tested_at' => 'datetime',
        'last_api_called_at' => 'datetime',
        'last_api_success_at' => 'datetime',
        'last_api_failure_at' => 'datetime',
        'last_webhook_received_at' => 'datetime',
        'webhook_verified_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function quotes()
    {
        return $this->hasMany(B2BFreightQuote::class, 'forwarder_id');
    }

    public function containerShipments()
    {
        return $this->hasMany(B2BContainerShipment::class, 'forwarder_id');
    }

    public function isSandbox(): bool
    {
        return $this->is_test_mode || strtolower((string) $this->environment) === 'sandbox';
    }

    public function credentialsConfigured(): bool
    {
        if (filled($this->oauth_token)) {
            return true;
        }

        if (is_array($this->credentials) && count(array_filter($this->credentials, fn ($value) => filled($value))) > 0) {
            return true;
        }

        if (filled($this->api_key) && filled($this->api_secret)) {
            return true;
        }

        return filled($this->username) && filled($this->password);
    }

    public function defaultQuoteAmounts(): array
    {
        return [
            'freight_cost' => (float) ($this->default_freight_cost ?? 0),
            'insurance_cost' => (float) ($this->default_insurance_cost ?? 0),
            'customs_estimate' => (float) ($this->default_customs_estimate ?? 0),
            'total_cost' => round(
                (float) ($this->default_freight_cost ?? 0)
                + (float) ($this->default_insurance_cost ?? 0)
                + (float) ($this->default_customs_estimate ?? 0),
                2
            ),
        ];
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
