<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CurrencyCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                return [
                    'id' => $data->id,
                    'name' => $data->name,
                    'code' => $data->code,
                    'symbol' => $data->symbol,
                    'exchange_rate' => (double) $data->exchange_rate,
                    'decimal_places' => (int) ($data->decimal_places ?? get_setting('no_of_decimals')),
                    'symbol_position' => $data->symbol_position ?? 'prefix',
                    'is_default' => get_setting('system_default_currency')==$data->id?true:false
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
