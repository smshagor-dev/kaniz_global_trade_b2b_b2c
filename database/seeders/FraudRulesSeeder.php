<?php

namespace Database\Seeders;

use App\Models\FraudRule;
use Illuminate\Database\Seeder;

class FraudRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['name' => 'Unverified Email', 'code' => 'unverified_email', 'score' => 10, 'severity' => 'low'],
            ['name' => 'Unverified Phone', 'code' => 'unverified_phone', 'score' => 15, 'severity' => 'medium'],
            ['name' => 'Missing Supplier Business Document', 'code' => 'supplier_missing_business_document', 'score' => 20, 'severity' => 'high'],
            ['name' => 'Rejected Supplier Document', 'code' => 'supplier_document_rejected', 'score' => 40, 'severity' => 'critical'],
            ['name' => 'Company Name Mismatch', 'code' => 'company_name_mismatch', 'score' => 35, 'severity' => 'high'],
            ['name' => 'Bank Company Name Mismatch', 'code' => 'bank_company_name_mismatch', 'score' => 40, 'severity' => 'critical'],
            ['name' => 'Duplicate IP Many Accounts', 'code' => 'duplicate_ip_many_accounts', 'score' => 25, 'severity' => 'high'],
            ['name' => 'Duplicate Device Many Accounts', 'code' => 'duplicate_device_many_accounts', 'score' => 30, 'severity' => 'critical'],
            ['name' => 'High RFQ Volume', 'code' => 'rfq_volume_high', 'score' => 30, 'severity' => 'high'],
            ['name' => 'Extreme RFQ Volume', 'code' => 'rfq_volume_extreme', 'score' => 50, 'severity' => 'critical'],
            ['name' => 'Many Payment Failures', 'code' => 'many_payment_failures', 'score' => 30, 'severity' => 'high'],
            ['name' => 'Reported By Users Medium', 'code' => 'reported_by_users_medium', 'score' => 35, 'severity' => 'high'],
            ['name' => 'Reported By Users High', 'code' => 'reported_by_users_high', 'score' => 60, 'severity' => 'critical'],
            ['name' => 'Very Low Product Price', 'code' => 'very_low_product_price', 'score' => 25, 'severity' => 'high'],
            ['name' => 'Duplicate Product Image', 'code' => 'duplicate_product_image', 'score' => 20, 'severity' => 'medium'],
            ['name' => 'Copied Product Description', 'code' => 'copied_product_description', 'score' => 15, 'severity' => 'medium'],
        ];

        foreach ($rules as $rule) {
            FraudRule::query()->updateOrCreate(
                ['code' => $rule['code']],
                [
                    'name' => $rule['name'],
                    'description' => $rule['name'],
                    'user_type' => null,
                    'event_type' => null,
                    'score' => $rule['score'],
                    'severity' => $rule['severity'],
                    'is_active' => true,
                ]
            );
        }
    }
}
