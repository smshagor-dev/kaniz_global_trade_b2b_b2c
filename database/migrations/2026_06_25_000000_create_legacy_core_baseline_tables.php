<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createLanguagesTable();
        $this->createAddonsTable();
        $this->createBusinessSettingsTable();
        $this->createCurrenciesTable();
        $this->createUsersTable();
        $this->createUploadsTable();
        $this->createBrandsTable();
        $this->createShopsTable();
        $this->createSellersTable();
        $this->createCategoriesTable();
        $this->createCategoryTranslationsTable();
        $this->createProductsTable();
        $this->createProductStocksTable();
        $this->createProductTranslationsTable();
        $this->createProductTaxesTable();
        $this->createProductQueriesTable();
        $this->createReviewsTable();
        $this->createOrdersTable();
        $this->createOrderDetailsTable();
        $this->createCartsTable();
        $this->createAddressesTable();
        $this->createCountriesTable();

        $this->seedCoreReferenceData();
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }

    protected function createLanguagesTable(): void
    {
        if (Schema::hasTable('languages')) {
            return;
        }

        Schema::create('languages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('code', 10)->unique();
            $table->unsignedTinyInteger('rtl')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    protected function createAddonsTable(): void
    {
        if (Schema::hasTable('addons')) {
            return;
        }

        Schema::create('addons', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name')->nullable();
            $table->string('unique_identifier')->nullable()->index();
            $table->string('version')->nullable();
            $table->unsignedTinyInteger('activated')->default(0);
            $table->string('image', 1000)->nullable();
            $table->string('purchase_code')->nullable();
            $table->timestamps();
        });
    }

    protected function createBusinessSettingsTable(): void
    {
        if (Schema::hasTable('business_settings')) {
            return;
        }

        Schema::create('business_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type')->index();
            $table->longText('value')->nullable();
            $table->string('lang', 30)->nullable();
            $table->timestamps();
        });
    }

    protected function createCurrenciesTable(): void
    {
        if (Schema::hasTable('currencies')) {
            return;
        }

        Schema::create('currencies', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->string('symbol', 20)->nullable();
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    protected function createUsersTable(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('referred_by')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_id', 50)->nullable();
            $table->text('refresh_token')->nullable();
            $table->longText('access_token')->nullable();
            $table->string('user_type', 20)->default('customer');
            $table->string('name', 191);
            $table->string('email', 191)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('verification_code')->nullable();
            $table->unsignedTinyInteger('verification_status')->default(1);
            $table->longText('verification_info')->nullable();
            $table->text('new_email_verificiation_code')->nullable();
            $table->string('password', 191)->nullable();
            $table->rememberToken();
            $table->string('device_token')->nullable();
            $table->string('avatar', 256)->nullable();
            $table->string('avatar_original', 256)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('country', 30)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('referral_code', 191)->nullable();
            $table->unsignedTinyInteger('banned')->default(0);
            $table->decimal('balance', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    protected function createUploadsTable(): void
    {
        if (Schema::hasTable('uploads')) {
            return;
        }

        Schema::create('uploads', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('file_original_name')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('extension', 10)->nullable();
            $table->string('type', 15)->nullable();
            $table->string('external_link', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function createBrandsTable(): void
    {
        if (Schema::hasTable('brands')) {
            return;
        }

        Schema::create('brands', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
            $table->integer('logo')->nullable();
            $table->unsignedTinyInteger('top')->default(0);
            $table->string('slug')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    protected function createShopsTable(): void
    {
        if (Schema::hasTable('shops')) {
            return;
        }

        Schema::create('shops', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('user_id')->index();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->unsignedTinyInteger('verification_status')->default(0);
            $table->decimal('admin_to_pay', 20, 2)->default(0);
            $table->unsignedInteger('seller_package_id')->nullable();
            $table->integer('product_upload_limit')->default(0);
            $table->integer('preorder_product_upload_limit')->default(0);
            $table->date('package_invalid_at')->nullable();
            $table->timestamps();
        });
    }

    protected function createSellersTable(): void
    {
        if (Schema::hasTable('sellers')) {
            return;
        }

        Schema::create('sellers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('user_id')->index();
            $table->unsignedTinyInteger('verification_status')->default(0);
            $table->timestamps();
        });
    }

    protected function createCategoriesTable(): void
    {
        if (Schema::hasTable('categories')) {
            return;
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('parent_id')->default(0);
            $table->integer('level')->default(0);
            $table->string('name', 50);
            $table->integer('order_level')->default(0);
            $table->double('commision_rate', 8, 2)->default(0);
            $table->double('discount', 20, 2)->default(0);
            $table->integer('discount_start_date')->nullable();
            $table->integer('discount_end_date')->nullable();
            $table->string('banner')->nullable();
            $table->string('icon')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedTinyInteger('featured')->default(0);
            $table->enum('hot_category', ['0', '1'])->default('0');
            $table->unsignedTinyInteger('top')->default(0);
            $table->unsignedTinyInteger('digital')->default(0);
            $table->string('slug')->nullable();
            $table->unsignedInteger('refund_request_time')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    protected function createCategoryTranslationsTable(): void
    {
        if (Schema::hasTable('category_translations')) {
            return;
        }

        Schema::create('category_translations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('category_id');
            $table->string('name', 50);
            $table->string('lang', 100);
            $table->timestamps();
        });
    }

    protected function createProductsTable(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name')->nullable();
            $table->string('added_by', 100)->default('customer');
            $table->unsignedInteger('user_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('brand_id')->nullable();
            $table->double('unit_price', 20, 2)->default(0);
            $table->longText('description')->nullable();
            $table->text('attributes')->nullable();
            $table->unsignedTinyInteger('published')->default(1);
            $table->unsignedTinyInteger('approved')->default(1);
            $table->integer('current_stock')->default(0);
            $table->string('unit', 50)->nullable();
            $table->double('weight', 20, 2)->nullable();
            $table->integer('min_qty')->default(1);
            $table->double('discount', 20, 2)->default(0);
            $table->string('discount_type', 20)->default('amount');
            $table->integer('discount_start_date')->nullable();
            $table->integer('discount_end_date')->nullable();
            $table->string('shipping_type', 30)->default('flat_rate');
            $table->double('shipping_cost', 20, 2)->default(0);
            $table->string('slug')->nullable()->unique();
            $table->unsignedTinyInteger('digital')->default(0);
            $table->unsignedTinyInteger('auction_product')->default(0);
            $table->unsignedTinyInteger('wholesale_product')->default(0);
            $table->unsignedTinyInteger('todays_deal')->default(0);
            $table->float('rating')->default(0);
            $table->text('photos')->nullable();
            $table->integer('thumbnail_img')->nullable();
            $table->timestamps();
        });
    }

    protected function createProductStocksTable(): void
    {
        if (Schema::hasTable('product_stocks')) {
            return;
        }

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('product_id')->index();
            $table->string('variant')->nullable();
            $table->string('sku')->nullable();
            $table->double('price', 20, 2)->default(0);
            $table->integer('qty')->default(0);
            $table->timestamps();
        });
    }

    protected function createProductTranslationsTable(): void
    {
        if (Schema::hasTable('product_translations')) {
            return;
        }

        Schema::create('product_translations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('product_id')->index();
            $table->string('name')->nullable();
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->string('lang', 100);
            $table->timestamps();
        });
    }

    protected function createProductTaxesTable(): void
    {
        if (Schema::hasTable('product_taxes')) {
            return;
        }

        Schema::create('product_taxes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('product_id')->index();
            $table->integer('tax_id')->nullable();
            $table->double('tax', 20, 2)->default(0);
            $table->string('tax_type', 20)->default('amount');
            $table->timestamps();
        });
    }

    protected function createProductQueriesTable(): void
    {
        if (Schema::hasTable('product_queries')) {
            return;
        }

        Schema::create('product_queries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('product_id')->index();
            $table->unsignedInteger('customer_id')->nullable();
            $table->text('question')->nullable();
            $table->text('reply')->nullable();
            $table->timestamps();
        });
    }

    protected function createReviewsTable(): void
    {
        if (Schema::hasTable('reviews')) {
            return;
        }

        Schema::create('reviews', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('product_id')->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedTinyInteger('rating')->default(0);
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    protected function createOrdersTable(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('combined_order_id')->nullable();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('payment_status', 30)->default('unpaid');
            $table->string('delivery_status', 30)->default('pending');
            $table->double('grand_total', 20, 2)->default(0);
            $table->text('payment_details')->nullable();
            $table->string('payment_type')->nullable();
            $table->unsignedTinyInteger('manual_payment')->default(0);
            $table->longText('manual_payment_data')->nullable();
            $table->unsignedTinyInteger('notified')->default(0);
            $table->timestamps();
        });
    }

    protected function createOrderDetailsTable(): void
    {
        if (Schema::hasTable('order_details')) {
            return;
        }

        Schema::create('order_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id')->index();
            $table->integer('product_id')->index();
            $table->unsignedInteger('seller_id')->nullable();
            $table->string('delivery_status', 30)->default('pending');
            $table->double('price', 20, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    protected function createCartsTable(): void
    {
        if (Schema::hasTable('carts')) {
            return;
        }

        Schema::create('carts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('status')->default(1);
            $table->integer('owner_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('temp_user_id')->nullable();
            $table->integer('address_id')->default(0);
            $table->integer('billing_address')->default(0);
            $table->integer('product_id')->nullable();
            $table->text('variation')->nullable();
            $table->double('price', 20, 2)->default(0);
            $table->double('tax', 20, 2)->default(0);
            $table->double('shipping_cost', 20, 2)->default(0);
            $table->string('shipping_type', 30)->default('');
            $table->integer('pickup_point')->nullable();
            $table->integer('carrier_id')->nullable();
            $table->double('discount', 10, 2)->default(0);
            $table->string('product_referral_code')->nullable();
            $table->string('coupon_code')->nullable();
            $table->unsignedTinyInteger('coupon_applied')->default(0);
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    protected function createAddressesTable(): void
    {
        if (Schema::hasTable('addresses')) {
            return;
        }

        Schema::create('addresses', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('user_id')->index();
            $table->string('address')->nullable();
            $table->integer('country_id')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('area_id')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedTinyInteger('set_default')->default(0);
            $table->string('set_billing', 20)->nullable();
            $table->timestamps();
        });
    }

    protected function createCountriesTable(): void
    {
        if (Schema::hasTable('countries')) {
            return;
        }

        Schema::create('countries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->integer('zone_id')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    protected function seedCoreReferenceData(): void
    {
        if (Schema::hasTable('languages') && !DB::table('languages')->where('code', 'en')->exists()) {
            DB::table('languages')->insert([
                'name' => 'English',
                'code' => 'en',
                'rtl' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('currencies') && !DB::table('currencies')->where('id', 1)->exists()) {
            DB::table('currencies')->insert([
                'id' => 1,
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!Schema::hasTable('business_settings')) {
            return;
        }

        $settings = [
            'home_default_currency' => '1',
            'system_default_currency' => '1',
            'currency_format' => '1',
            'symbol_format' => '1',
            'decimal_separator' => '1',
            'no_of_decimals' => '2',
            'homepage_select' => 'classic',
            'authentication_layout_select' => 'default',
            'guest_checkout_activation' => '0',
            'vendor_system_activation' => '1',
            'shipping_type' => 'flat_rate',
            'sslcommerz_sandbox' => '1',
            'bkash_sandbox' => '1',
            'myfatoorah' => '0',
            'myfatoorah_sandbox' => '1',
            'conversation_system' => '0',
            'email_verification' => '0',
            'google_recaptcha' => '0',
            'has_state' => '0',
            'last_viewed_product_activation' => '0',
            'facebook_pixel_capi' => '0',
            'cash_payment' => '1',
        ];

        foreach ($settings as $type => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $type, 'lang' => null],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
};
