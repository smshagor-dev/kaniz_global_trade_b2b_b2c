<?php

namespace Database\Seeders;

use App\Services\Fraud\FraudSettingsService;
use Illuminate\Database\Seeder;

class FraudSettingsSeeder extends Seeder
{
    public function run(): void
    {
        app(FraudSettingsService::class)->update(
            app(FraudSettingsService::class)->defaults()
        );
    }
}
