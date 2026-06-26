<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BShipmentEvent extends Model
{
    protected $table = 'b2b_shipment_events';

    protected $fillable = [
        'shipment_id',
        'created_by',
        'status',
        'carrier_event',
        'title',
        'location',
        'city',
        'country',
        'description',
        'event_at',
    ];

    protected $casts = [
        'event_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(B2BShipment::class, 'shipment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
