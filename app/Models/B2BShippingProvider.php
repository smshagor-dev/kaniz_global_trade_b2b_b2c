<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BShippingProvider extends Model
{
    protected $table = 'b2b_shipping_providers';

    public const PROVIDER_TYPES = ['manual', 'api'];
    public const API_DRIVERS = [
        'dhl',
        'fedex',
        'ups',
        'aramex',
        'tnt',
        'dpd',
        'gls',
        'pathao',
        'redx',
        'paperfly',
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
    public const DRIVER_LABELS = [
        'dhl' => 'DHL',
        'fedex' => 'FedEx',
        'ups' => 'UPS',
        'aramex' => 'Aramex',
        'tnt' => 'TNT',
        'dpd' => 'DPD',
        'gls' => 'GLS',
        'pathao' => 'Pathao',
        'redx' => 'RedX',
        'paperfly' => 'Paperfly',
        'maersk' => 'Maersk',
        'msc' => 'MSC',
        'cma_cgm' => 'CMA CGM',
        'hapag_lloyd' => 'Hapag-Lloyd',
        'cosco' => 'COSCO',
        'evergreen' => 'Evergreen',
        'one' => 'ONE',
        'dp_world' => 'DP World',
        'freightos' => 'Freightos',
        'flexport' => 'Flexport',
        'custom' => 'Custom',
    ];
    public const MODE_DRIVER_MAP = [
        'air_freight' => ['dhl', 'fedex', 'ups', 'aramex', 'tnt', 'freightos', 'flexport', 'custom'],
        'sea_freight' => ['maersk', 'msc', 'cma_cgm', 'hapag_lloyd', 'cosco', 'evergreen', 'one', 'dp_world', 'freightos', 'flexport', 'custom'],
        'rail' => ['dp_world', 'flexport', 'custom'],
        'truck' => ['dpd', 'gls', 'pathao', 'redx', 'paperfly', 'flexport', 'custom'],
        'courier' => ['dhl', 'fedex', 'ups', 'aramex', 'tnt', 'dpd', 'gls', 'pathao', 'redx', 'paperfly', 'custom'],
    ];
    public const DRIVER_CAPABILITIES = [
        'maersk' => ['port_to_port', 'door_to_port', 'door_to_door', 'container_tracking', 'bill_of_lading_tracking', 'fcl_quote', 'lcl_quote', 'hs_code_customs_handling'],
        'msc' => ['port_to_port', 'door_to_port', 'door_to_door', 'container_tracking', 'bill_of_lading_tracking', 'fcl_quote', 'lcl_quote', 'hs_code_customs_handling'],
        'cma_cgm' => ['port_to_port', 'door_to_port', 'door_to_door', 'container_tracking', 'bill_of_lading_tracking', 'fcl_quote', 'lcl_quote', 'hs_code_customs_handling'],
        'hapag_lloyd' => ['port_to_port', 'door_to_port', 'door_to_door', 'container_tracking', 'bill_of_lading_tracking', 'fcl_quote', 'lcl_quote', 'hs_code_customs_handling'],
        'cosco' => ['port_to_port', 'door_to_port', 'door_to_door', 'container_tracking', 'bill_of_lading_tracking', 'fcl_quote', 'lcl_quote', 'hs_code_customs_handling'],
        'evergreen' => ['port_to_port', 'door_to_port', 'door_to_door', 'container_tracking', 'bill_of_lading_tracking', 'fcl_quote', 'lcl_quote', 'hs_code_customs_handling'],
        'one' => ['port_to_port', 'door_to_port', 'door_to_door', 'container_tracking', 'bill_of_lading_tracking', 'fcl_quote', 'lcl_quote', 'hs_code_customs_handling'],
        'dp_world' => ['port_to_port', 'port_to_door', 'door_to_door', 'cargo_visibility', 'container_tracking', 'customs_handling'],
        'freightos' => ['port_to_port', 'door_to_port', 'port_to_door', 'door_to_door', 'fcl_quote', 'lcl_quote', 'cargo_visibility', 'customs_handling'],
        'flexport' => ['port_to_port', 'door_to_port', 'port_to_door', 'door_to_door', 'cargo_visibility', 'container_tracking', 'customs_handling'],
    ];

    protected $fillable = [
        'name',
        'transport_mode',
        'provider_type',
        'api_driver',
        'api_base_url',
        'api_key',
        'api_secret',
        'account_number',
        'username',
        'password',
        'oauth_token',
        'refresh_token',
        'environment',
        'custom_config',
        'webhook_secret',
        'integration_events',
        'website',
        'contact_email',
        'contact_phone',
        'supported_services',
        'supported_countries',
        'default_shipping_cost',
        'default_insurance_amount',
        'default_customs_estimate',
        'is_test_mode',
        'is_active',
        'is_verified',
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
        'account_number' => 'encrypted',
        'username' => 'encrypted',
        'password' => 'encrypted',
        'oauth_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'custom_config' => 'array',
        'integration_events' => 'array',
        'supported_countries' => 'array',
        'supported_services' => 'array',
        'default_shipping_cost' => 'decimal:2',
        'default_insurance_amount' => 'decimal:2',
        'default_customs_estimate' => 'decimal:2',
        'is_test_mode' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'last_api_called_at' => 'datetime',
        'last_api_success_at' => 'datetime',
        'last_api_failure_at' => 'datetime',
        'last_webhook_received_at' => 'datetime',
        'webhook_verified_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function shippingQuotes()
    {
        return $this->hasMany(B2BShippingQuote::class, 'shipping_provider_id');
    }

    public function shipments()
    {
        return $this->hasMany(B2BShipment::class, 'shipping_provider_id');
    }

    public function isApiProvider(): bool
    {
        return $this->provider_type === 'api';
    }

    public static function driversForMode(?string $mode): array
    {
        if (!$mode || !isset(self::MODE_DRIVER_MAP[$mode])) {
            return self::API_DRIVERS;
        }

        return self::MODE_DRIVER_MAP[$mode];
    }

    public static function driverLabel(string $driver): string
    {
        return self::DRIVER_LABELS[$driver] ?? strtoupper(str_replace('_', ' ', $driver));
    }

    public function defaultQuoteAmounts(): array
    {
        return [
            'shipping_cost' => (float) ($this->default_shipping_cost ?? 0),
            'insurance_amount' => (float) ($this->default_insurance_amount ?? 0),
            'customs_estimate' => (float) ($this->default_customs_estimate ?? 0),
        ];
    }

    public function credentialsConfigured(): bool
    {
        if (!$this->isApiProvider()) {
            return true;
        }

        $driver = $this->api_driver;

        return match ($driver) {
            'dhl', 'fedex', 'ups' => filled($this->api_key) && filled($this->api_secret) && filled($this->account_number),
            'aramex' => filled($this->username) && filled($this->password) && filled($this->account_number) && filled($this->api_key) && filled($this->api_secret),
            default => filled($this->api_key) && filled($this->api_secret),
        };
    }

    public function isSandbox(): bool
    {
        return $this->is_test_mode || strtolower((string) $this->environment) === 'sandbox';
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
