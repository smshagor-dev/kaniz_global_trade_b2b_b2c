<?php

namespace Tests\Feature\B2B;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Middlewares\RoleMiddleware;
use Spatie\Permission\Middlewares\RoleOrPermissionMiddleware;
use Tests\TestCase;

abstract class B2BFeatureTestCase extends TestCase
{
    use DatabaseTransactions;
    use Concerns\BuildsB2BData;

    protected static bool $b2bFreightSchemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

        Notification::fake();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(PermissionMiddleware::class);
        $this->withoutMiddleware(RoleMiddleware::class);
        $this->withoutMiddleware(RoleOrPermissionMiddleware::class);

        if (!Route::has('auction_product_bids.store')) {
            $this->app['router']->post('/__test/auction-product-bids', static fn () => response()->noContent())
                ->name('auction_product_bids.store');
            $this->app['router']->getRoutes()->refreshNameLookups();
        }

        $requiredCoreTables = [
            'languages',
            'addons',
            'business_settings',
            'currencies',
            'users',
            'uploads',
            'brands',
            'shops',
            'sellers',
            'categories',
            'category_translations',
            'products',
            'product_stocks',
            'product_translations',
            'product_taxes',
            'product_queries',
            'reviews',
            'orders',
            'order_details',
            'carts',
            'addresses',
            'countries',
        ];

        $requiredFreightTables = [
            'b2b_ports',
            'b2b_freight_forwarders',
            'b2b_freight_quotes',
            'b2b_freight_quote_costs',
            'b2b_freight_pricing_rules',
            'b2b_hs_codes',
            'b2b_container_shipments',
            'b2b_container_events',
            'b2b_customs_documents',
            'currency_api_settings',
            'currency_exchange_rates',
            'currency_rate_history',
            'b2b_payment_transactions',
            'b2b_escrows',
            'b2b_escrow_logs',
            'b2b_payment_milestones',
            'b2b_letter_of_credits',
            'b2b_settlements',
            'b2b_settlement_logs',
            'b2b_finance_disputes',
            'b2b_finance_dispute_messages',
            'b2b_finance_refunds',
        ];

        $missingTables = collect(array_merge($requiredCoreTables, $requiredFreightTables))
            ->reject(fn ($table) => Schema::hasTable($table))
            ->values();

        if (!static::$b2bFreightSchemaReady && $missingTables->isNotEmpty()) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if (Schema::hasTable('b2b_proforma_invoices') && !Schema::hasColumn('b2b_proforma_invoices', 'escrow_status')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if (Schema::hasTable('b2b_rfqs') && !Schema::hasColumn('b2b_rfqs', 'exchange_rate_snapshot')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if (Schema::hasTable('b2b_package_requests') && !Schema::hasColumn('b2b_package_requests', 'payment_reference')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        static::$b2bFreightSchemaReady = collect($requiredFreightTables)->every(fn ($table) => Schema::hasTable($table));
    }
}
