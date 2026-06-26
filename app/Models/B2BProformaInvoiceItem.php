<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BProformaInvoiceItem extends Model
{
    protected $table = 'b2b_proforma_invoice_items';

    protected $fillable = [
        'proforma_invoice_id',
        'product_id',
        'product_name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'tax_amount',
        'discount_amount',
        'line_total',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function proformaInvoice()
    {
        return $this->belongsTo(B2BProformaInvoice::class, 'proforma_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
