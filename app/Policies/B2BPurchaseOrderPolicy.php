<?php

namespace App\Policies;

use App\Models\B2BPurchaseOrder;
use App\Models\User;

class B2BPurchaseOrderPolicy
{
    public function view(User $user, B2BPurchaseOrder $purchaseOrder): bool
    {
        return $user->user_type === 'admin'
            || $purchaseOrder->buyer_user_id === $user->id
            || $purchaseOrder->supplier_user_id === $user->id;
    }

    public function buyerManage(User $user, B2BPurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->buyer_user_id === $user->id;
    }

    public function supplierReview(User $user, B2BPurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->supplier_user_id === $user->id;
    }
}
