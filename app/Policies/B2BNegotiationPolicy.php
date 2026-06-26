<?php

namespace App\Policies;

use App\Models\B2BNegotiation;
use App\Models\User;

class B2BNegotiationPolicy
{
    public function view(User $user, B2BNegotiation $negotiation): bool
    {
        return $user->user_type === 'admin'
            || $negotiation->buyer_user_id === $user->id
            || $negotiation->supplier_user_id === $user->id;
    }

    public function participate(User $user, B2BNegotiation $negotiation): bool
    {
        return $negotiation->buyer_user_id === $user->id
            || $negotiation->supplier_user_id === $user->id;
    }
}
