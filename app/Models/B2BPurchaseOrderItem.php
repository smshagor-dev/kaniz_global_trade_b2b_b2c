<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BPurchaseOrderItem extends Model
{
    protected $table = 'b2b_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'line_total',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
