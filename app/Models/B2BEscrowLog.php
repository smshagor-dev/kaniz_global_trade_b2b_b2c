<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BEscrowLog extends Model
{
    protected $table = 'b2b_escrow_logs';

    protected $fillable = [
        'escrow_id',
        'action',
        'performed_by',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function escrow()
    {
        return $this->belongsTo(B2BEscrow::class, 'escrow_id');
    }
}
