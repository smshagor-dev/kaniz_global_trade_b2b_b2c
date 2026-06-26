<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BContainerEvent extends Model
{
    protected $table = 'b2b_container_events';

    protected $fillable = [
        'container_shipment_id',
        'event_type',
        'port_location',
        'port_id',
        'vessel_name',
        'voyage_number',
        'description',
        'source_provider',
        'event_at',
    ];

    protected $casts = [
        'event_at' => 'datetime',
    ];

    public function containerShipment()
    {
        return $this->belongsTo(B2BContainerShipment::class, 'container_shipment_id');
    }

    public function port()
    {
        return $this->belongsTo(B2BPort::class, 'port_id');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
