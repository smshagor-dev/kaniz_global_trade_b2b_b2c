<?php

namespace App\Policies;

use App\Models\B2BProformaInvoice;
use App\Models\User;

class B2BProformaInvoicePolicy
{
    public function view(User $user, B2BProformaInvoice $invoice): bool
    {
        return $user->user_type === 'admin'
            || $invoice->buyer_user_id === $user->id
            || $invoice->supplier_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'seller';
    }

    public function manage(User $user, B2BProformaInvoice $invoice): bool
    {
        return $invoice->supplier_user_id === $user->id;
    }
}
