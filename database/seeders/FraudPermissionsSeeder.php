<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class FraudPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'fraud.view',
            'fraud.manage',
            'fraud.review_documents',
            'fraud.run_ai_check',
            'fraud.block_users',
            'fraud.settings',
        ] as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ], [
                'section' => 'fraud',
            ]);
        }
    }
}
