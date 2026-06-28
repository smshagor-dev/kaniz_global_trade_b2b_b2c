<?php

namespace App\Services\Fraud;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;

class FraudSettingsService
{
    public const PREFIX = 'fraud_';

    public function defaults(): array
    {
        return [
            'enabled' => true,
            'ai_enabled' => false,
            'manual_approval_suppliers' => true,
            'manual_approval_buyers' => false,
            'manual_review_threshold' => 41,
            'restriction_threshold' => 81,
            'block_threshold' => 100,
            'ai_weight_percentage' => 35,
            'rule_weight_percentage' => 65,
            'rfq_limit_per_day' => 20,
            'product_upload_limit_per_day' => 20,
            'auto_block_enabled' => false,
            'notify_admin_high_risk' => true,
            'notify_user_verification_rejected' => true,
        ];
    }

    public function all(): array
    {
        return Cache::remember('fraud_settings', 300, function () {
            $settings = $this->defaults();

            foreach ($settings as $key => $default) {
                $value = BusinessSetting::query()->where('type', self::PREFIX . $key)->value('value');
                if ($value === null) {
                    continue;
                }

                $settings[$key] = is_bool($default)
                    ? $value === '1'
                    : (is_int($default) ? (int) $value : $value);
            }

            return $settings;
        });
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $fallback;
    }

    public function update(array $values): void
    {
        $defaults = $this->defaults();

        foreach ($defaults as $key => $default) {
            $value = $values[$key] ?? $default;

            BusinessSetting::query()->updateOrCreate(
                ['type' => self::PREFIX . $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        Cache::forget('fraud_settings');
    }
}
