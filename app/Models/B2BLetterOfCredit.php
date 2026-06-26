<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BLetterOfCredit extends Model
{
    protected $table = 'b2b_letter_of_credits';

    protected $fillable = [
        'purchase_order_id',
        'proforma_invoice_id',
        'buyer_company_id',
        'supplier_company_id',
        'lc_number',
        'issuing_bank',
        'advising_bank',
        'expiry_date',
        'amount',
        'currency',
        'required_documents',
        'status',
        'review_notes',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'amount' => 'decimal:2',
        'required_documents' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
