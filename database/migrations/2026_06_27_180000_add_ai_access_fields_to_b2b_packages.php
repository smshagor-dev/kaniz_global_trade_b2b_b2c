<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_packages')) {
            return;
        }

        Schema::table('b2b_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_packages', 'ai_access')) {
                $table->boolean('ai_access')->default(false)->after('dedicated_support');
            }

            if (!Schema::hasColumn('b2b_packages', 'ai_rfq_access')) {
                $table->boolean('ai_rfq_access')->default(false)->after('ai_access');
            }

            if (!Schema::hasColumn('b2b_packages', 'ai_product_description_access')) {
                $table->boolean('ai_product_description_access')->default(false)->after('ai_rfq_access');
            }

            if (!Schema::hasColumn('b2b_packages', 'ai_negotiation_access')) {
                $table->boolean('ai_negotiation_access')->default(false)->after('ai_product_description_access');
            }

            if (!Schema::hasColumn('b2b_packages', 'ai_translation_access')) {
                $table->boolean('ai_translation_access')->default(false)->after('ai_negotiation_access');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('b2b_packages')) {
            return;
        }

        Schema::table('b2b_packages', function (Blueprint $table) {
            foreach ([
                'ai_translation_access',
                'ai_negotiation_access',
                'ai_product_description_access',
                'ai_rfq_access',
                'ai_access',
            ] as $column) {
                if (Schema::hasColumn('b2b_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
