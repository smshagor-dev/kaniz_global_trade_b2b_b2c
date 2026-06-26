<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_rfqs') || !Schema::hasTable('b2b_quotations')) {
            return;
        }

        DB::statement('ALTER TABLE `b2b_rfqs` MODIFY `user_id` INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `b2b_rfqs` MODIFY `product_id` INT NULL');

        DB::statement('ALTER TABLE `b2b_quotations` MODIFY `supplier_user_id` INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `b2b_quotations` MODIFY `product_id` INT NULL');

        if (Schema::hasTable('b2b_purchase_order_items')) {
            DB::statement('ALTER TABLE `b2b_purchase_order_items` MODIFY `product_id` INT NULL');
        }

        if (Schema::hasTable('b2b_proforma_invoice_items')) {
            DB::statement('ALTER TABLE `b2b_proforma_invoice_items` MODIFY `product_id` INT NULL');
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
