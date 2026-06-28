<?php

namespace App\Services\Fraud;

use App\Models\FraudCheck;
use App\Models\User;

class FraudRestrictionService
{
    public function latestCheck(User|int $user): ?FraudCheck
    {
        $userId = $user instanceof User ? $user->id : $user;

        return FraudCheck::query()->where('user_id', $userId)->latest('updated_at')->first();
    }

    public function isBlocked(User|int $user): bool
    {
        $check = $this->latestCheck($user);

        return (bool) ($check && ($check->status === 'blocked' || $check->risk_level === 'blocked'));
    }

    public function canCreateRfq(User|int $user): bool
    {
        $check = $this->latestCheck($user);

        if (!$check) {
            return true;
        }

        return !in_array($check->risk_level, ['critical', 'blocked'], true)
            && $check->status !== 'blocked';
    }

    public function canReplyToRfq(User|int $user): bool
    {
        $check = $this->latestCheck($user);

        if (!$check) {
            return true;
        }

        return !in_array($check->risk_level, ['high', 'critical', 'blocked'], true)
            && !in_array($check->status, ['blocked', 'restricted'], true);
    }

    public function userFacingStatus(?FraudCheck $check): array
    {
        if (!$check) {
            return [
                'label' => translate('Your verification is under review.'),
                'tone' => 'info',
            ];
        }

        return match (true) {
            $check->status === 'approved' && in_array($check->risk_level, ['safe', 'low'], true) => [
                'label' => translate('Your account is verified.'),
                'tone' => 'success',
            ],
            in_array($check->status, ['pending', 'needs_review'], true) => [
                'label' => translate('Your verification is under review.'),
                'tone' => 'warning',
            ],
            $check->status === 'rejected' => [
                'label' => translate('Your verification was rejected. Please upload valid documents.'),
                'tone' => 'danger',
            ],
            in_array($check->status, ['restricted', 'blocked'], true) || in_array($check->risk_level, ['high', 'critical', 'blocked'], true) => [
                'label' => translate('Your account has limited access pending verification.'),
                'tone' => 'danger',
            ],
            default => [
                'label' => translate('Please upload business documents.'),
                'tone' => 'info',
            ],
        };
    }
}
