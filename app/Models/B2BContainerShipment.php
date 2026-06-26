<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BContainerShipment extends Model
{
    protected $table = 'b2b_container_shipments';

    protected $fillable = [
        'freight_quote_id',
        'shipment_id',
        'forwarder_id',
        'booking_number',
        'bill_of_lading_number',
        'container_number',
        'seal_number',
        'vessel_name',
        'voyage_number',
        'origin_port_id',
        'destination_port_id',
        'transshipment_port_id',
        'etd',
        'eta',
        'ata',
        'status',
        'current_location',
        'current_location_port_id',
        'source_provider',
        'tracking_reference',
        'total_freight_cost',
        'landed_cost_total',
        'sync_error',
        'request_payload',
        'last_response',
        'last_synced_at',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'ata' => 'datetime',
        'total_freight_cost' => 'decimal:2',
        'landed_cost_total' => 'decimal:2',
        'request_payload' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function freightQuote()
    {
        return $this->belongsTo(B2BFreightQuote::class, 'freight_quote_id');
    }

    public function shipment()
    {
        return $this->belongsTo(B2BShipment::class, 'shipment_id');
    }

    public function forwarder()
    {
        return $this->belongsTo(B2BFreightForwarder::class, 'forwarder_id');
    }

    public function originPort()
    {
        return $this->belongsTo(B2BPort::class, 'origin_port_id');
    }

    public function destinationPort()
    {
        return $this->belongsTo(B2BPort::class, 'destination_port_id');
    }

    public function transshipmentPort()
    {
        return $this->belongsTo(B2BPort::class, 'transshipment_port_id');
    }

    public function currentLocationPort()
    {
        return $this->belongsTo(B2BPort::class, 'current_location_port_id');
    }

    public function events()
    {
        return $this->hasMany(B2BContainerEvent::class, 'container_shipment_id')->orderBy('event_at');
    }

    public function customsDocuments()
    {
        return $this->morphMany(B2BCustomsDocument::class, 'documentable');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
