<?php

namespace Tests\Feature\B2B\Concerns;

use App\Models\B2BCompany;
use App\Models\B2BCompanyMember;
use App\Models\B2BContainerShipment;
use App\Models\B2BCustomsDocument;
use App\Models\B2BFreightForwarder;
use App\Models\B2BPackage;
use App\Models\B2BFreightPricingRule;
use App\Models\B2BFreightQuote;
use App\Models\B2BFreightQuoteCost;
use App\Models\B2BHsCode;
use App\Models\B2BPort;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BQuotation;
use App\Models\B2BRfq;
use App\Models\B2BSampleOrder;
use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use App\Models\B2BShippingQuote;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\B2BTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait BuildsB2BData
{
    protected function createUser(array $attributes = []): User
    {
        $sequence = random_int(1000, 999999);

        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Test User ' . $sequence,
            'email' => 'user' . $sequence . '@example.test',
            'password' => bcrypt('password'),
            'user_type' => 'customer',
            'banned' => 0,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ], $attributes));
        $user->save();

        return $user->fresh();
    }

    protected function createSellerUser(array $userAttributes = []): User
    {
        $user = $this->createUser(array_merge(['user_type' => 'seller'], $userAttributes));

        DB::table('shops')->insert([
            'user_id' => $user->id,
            'name' => $user->name . ' Shop',
            'slug' => Str::slug($user->name . '-shop-' . $user->id . '-' . Str::random(5)),
            'verification_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sellers')->insert([
            'user_id' => $user->id,
            'verification_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }

    protected function createAdminUser(array $attributes = []): User
    {
        return $this->createUser(array_merge(['user_type' => 'admin'], $attributes));
    }

    protected function createCategory(array $attributes = []): Category
    {
        $sequence = random_int(1000, 999999);

        $id = DB::table('categories')->insertGetId(array_merge([
            'parent_id' => 0,
            'level' => 0,
            'name' => 'Category ' . $sequence,
            'order_level' => 0,
            'commision_rate' => 0,
            'discount' => 0,
            'featured' => 0,
            'hot_category' => '0',
            'top' => 0,
            'digital' => 0,
            'slug' => 'category-' . $sequence,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Category::findOrFail($id);
    }

    protected function createProduct(User $seller, ?Category $category = null, array $attributes = []): Product
    {
        $category ??= $this->createCategory();
        $sequence = random_int(1000, 999999);

        $productId = DB::table('products')->insertGetId(array_merge([
            'name' => 'Product ' . $sequence,
            'added_by' => $seller->user_type === 'seller' ? 'seller' : 'admin',
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'unit_price' => 100,
            'attributes' => '[]',
            'published' => 1,
            'approved' => 1,
            'current_stock' => 50,
            'unit' => 'pcs',
            'weight' => 1,
            'min_qty' => 1,
            'discount' => 0,
            'discount_type' => 'amount',
            'shipping_type' => 'flat_rate',
            'shipping_cost' => 0,
            'slug' => 'product-' . $sequence,
            'digital' => 0,
            'auction_product' => 0,
            'wholesale_product' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        DB::table('product_stocks')->insert([
            'product_id' => $productId,
            'variant' => '',
            'sku' => 'SKU-' . $sequence,
            'price' => $attributes['unit_price'] ?? 100,
            'qty' => $attributes['current_stock'] ?? 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_translations')->insert([
            'product_id' => $productId,
            'name' => $attributes['name'] ?? ('Product ' . $sequence),
            'unit' => $attributes['unit'] ?? 'pcs',
            'description' => $attributes['description'] ?? 'Test product description',
            'lang' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Product::findOrFail($productId);
    }

    protected function createCompany(User $owner, array $attributes = []): B2BCompany
    {
        $sequence = random_int(1000, 999999);

        $company = B2BCompany::create(array_merge([
            'user_id' => $owner->id,
            'company_name' => 'Company ' . $sequence,
            'company_type' => 'buyer',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'phone' => '01700000000',
            'business_email' => 'company' . $sequence . '@example.test',
            'verification_status' => 'approved',
        ], $attributes));

        if (
            !$company->b2b_package_id
            && Schema::hasTable('b2b_packages')
        ) {
            $packageRole = in_array($company->company_type, ['supplier', 'manufacturer', 'distributor', 'wholesaler'], true)
                ? 'supplier'
                : 'buyer';

            $package = B2BPackage::query()
                ->where('package_for', $packageRole)
                ->where('is_active', true)
                ->orderBy('amount')
                ->orderBy('sort_order')
                ->first();

            if ($package) {
                $company->update([
                    'b2b_package_id' => $package->id,
                    'package_started_at' => now(),
                    'package_expires_at' => $package->duration > 0 ? now()->copy()->addDays($package->duration) : null,
                ]);
            }
        }

        return $company->fresh();
    }

    protected function createCompanyMember(B2BCompany $company, User $user, string $role = 'admin', string $status = 'active'): B2BCompanyMember
    {
        return B2BCompanyMember::updateOrCreate(
            [
                'b2b_company_id' => $company->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role,
                'status' => $status,
                'joined_at' => now(),
            ]
        );
    }

    protected function setActiveCompany(B2BCompany $company): void
    {
        session(['active_b2b_company_id' => $company->id]);
    }

    protected function createRfq(B2BCompany $buyerCompany, User $buyerUser, array $attributes = []): B2BRfq
    {
        return B2BRfq::create(array_merge([
            'user_id' => $buyerUser->id,
            'b2b_company_id' => $buyerCompany->id,
            'title' => 'Need bulk supply',
            'description' => 'Bulk RFQ description',
            'quantity' => 100,
            'unit' => 'pcs',
            'target_price' => 25,
            'currency' => 'USD',
            'incoterm' => 'FOB',
            'destination_country' => 'Bangladesh',
            'destination_city' => 'Dhaka',
            'status' => 'open',
            'expires_at' => now()->addDays(7),
        ], $attributes));
    }

    protected function createQuotation(B2BRfq $rfq, B2BCompany $supplierCompany, User $supplierUser, array $attributes = []): B2BQuotation
    {
        return B2BQuotation::create(array_merge([
            'rfq_id' => $rfq->id,
            'supplier_user_id' => $supplierUser->id,
            'supplier_company_id' => $supplierCompany->id,
            'price' => 20,
            'currency' => 'USD',
            'moq' => 10,
            'lead_time_days' => 15,
            'shipping_terms' => 'Sea freight',
            'incoterm' => 'FOB',
            'payment_terms' => '30% advance',
            'message' => 'Test quotation',
            'status' => 'pending',
        ], $attributes));
    }

    protected function createPurchaseOrder(B2BQuotation $quotation, array $attributes = []): B2BPurchaseOrder
    {
        $purchaseOrder = app(B2BTransactionService::class)->createPurchaseOrderFromQuotation(
            $quotation->fresh(['rfq.company', 'rfq.product', 'supplierCompany', 'supplier'])
        );

        if ($attributes) {
            $purchaseOrder->update($attributes);
        }

        return $purchaseOrder->fresh();
    }

    protected function createProformaInvoice(B2BPurchaseOrder $purchaseOrder, array $payload = []): B2BProformaInvoice
    {
        $defaultPayload = [
            'currency' => $purchaseOrder->currency ?: 'USD',
            'incoterm' => $purchaseOrder->incoterms ?: 'FOB',
            'subtotal' => (float) $purchaseOrder->subtotal,
            'tax_amount' => 0,
            'shipping_amount' => 10,
            'discount_amount' => 0,
            'grand_total' => (float) $purchaseOrder->subtotal + 10,
            'valid_until' => now()->addDays(10)->toDateString(),
            'notes' => 'Invoice notes',
            'status' => 'draft',
            'items' => [[
                'product_id' => optional($purchaseOrder->items()->first())->product_id,
                'product_name' => optional($purchaseOrder->items()->first())->product_name ?? 'Line Item',
                'description' => 'Invoice line',
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => (float) $purchaseOrder->total_amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'line_total' => (float) $purchaseOrder->total_amount,
            ]],
        ];

        return app(B2BTransactionService::class)->createProformaInvoiceFromPurchaseOrder(
            $purchaseOrder->fresh(),
            array_merge($defaultPayload, $payload)
        );
    }

    protected function createSampleOrder(B2BCompany $buyerCompany, User $buyerUser, B2BCompany $supplierCompany, User $supplierUser, array $attributes = []): B2BSampleOrder
    {
        return B2BSampleOrder::create(array_merge([
            'sample_number' => 'SO-' . now()->format('Ymd') . '-' . random_int(10000, 99999),
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'buyer_user_id' => $buyerUser->id,
            'supplier_user_id' => $supplierUser->id,
            'currency' => 'USD',
            'quantity' => 2,
            'unit' => 'pcs',
            'sample_price' => 20,
            'shipping_amount' => 0,
            'total_amount' => 20,
            'status' => 'requested',
            'requested_at' => now(),
        ], $attributes));
    }

    protected function createShippingProvider(array $attributes = []): B2BShippingProvider
    {
        return B2BShippingProvider::create((new B2BShippingProvider())->filterPersistable(array_merge([
            'name' => 'Global Freight',
            'transport_mode' => 'sea_freight',
            'provider_type' => 'manual',
            'api_driver' => null,
            'contact_email' => 'freight@example.test',
            'default_shipping_cost' => 100,
            'default_insurance_amount' => 10,
            'default_customs_estimate' => 20,
            'is_test_mode' => true,
            'is_active' => true,
            'is_verified' => true,
        ], $attributes)));
    }

    protected function createShippingQuote(array $attributes = []): B2BShippingQuote
    {
        return B2BShippingQuote::create((new B2BShippingQuote())->filterPersistable(array_merge([
            'quote_number' => 'SQ-' . now()->format('Ymd') . '-' . random_int(10000, 99999),
            'supplier_company_id' => null,
            'buyer_company_id' => null,
            'created_by' => null,
            'transport_mode' => 'sea_freight',
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'incoterm' => 'FOB',
            'currency' => 'USD',
            'shipping_cost' => 100,
            'insurance_amount' => 10,
            'customs_estimate' => 20,
            'status' => 'submitted',
        ], $attributes)));
    }

    protected function createShipment(array $attributes = []): B2BShipment
    {
        return B2BShipment::create((new B2BShipment())->filterPersistable(array_merge([
            'shipment_number' => 'SH-' . now()->format('Ymd') . '-' . random_int(10000, 99999),
            'supplier_company_id' => null,
            'buyer_company_id' => null,
            'created_by' => null,
            'transport_mode' => 'sea_freight',
            'incoterm' => 'FOB',
            'status' => 'preparing',
            'live_tracking_enabled' => false,
        ], $attributes)));
    }

    protected function createPort(array $attributes = []): B2BPort
    {
        $sequence = random_int(1000, 999999);

        return B2BPort::create((new B2BPort())->filterPersistable(array_merge([
            'name' => 'Port ' . $sequence,
            'code' => 'PORT' . $sequence,
            'country' => 'Bangladesh',
            'city' => 'Chittagong',
            'unlocode' => 'BDCGP',
            'timezone' => 'Asia/Dhaka',
            'port_type' => 'sea',
            'is_active' => true,
        ], $attributes)));
    }

    protected function createFreightForwarder(array $attributes = []): B2BFreightForwarder
    {
        return B2BFreightForwarder::create((new B2BFreightForwarder())->filterPersistable(array_merge([
            'name' => 'Oceanic Forwarder',
            'driver' => 'maersk',
            'api_base_url' => 'https://api.freight.example.test',
            'api_key' => 'test-api-key',
            'api_secret' => 'test-api-secret',
            'environment' => 'sandbox',
            'is_test_mode' => true,
            'is_active' => true,
            'supported_modes' => ['sea_freight'],
            'supported_services' => ['port_to_port', 'fcl'],
            'default_freight_cost' => 500,
            'default_insurance_cost' => 50,
            'default_customs_estimate' => 30,
        ], $attributes)));
    }

    protected function createFreightQuote(array $attributes = []): B2BFreightQuote
    {
        return B2BFreightQuote::create((new B2BFreightQuote())->filterPersistable(array_merge([
            'quote_number' => 'FQ-' . now()->format('Ymd') . '-' . random_int(10000, 99999),
            'buyer_company_id' => null,
            'supplier_company_id' => null,
            'forwarder_id' => null,
            'created_by' => null,
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'freight_mode' => 'sea_freight',
            'service_type' => 'port_to_port',
            'incoterm' => 'FOB',
            'container_type' => '40HC',
            'container_count' => 1,
            'cargo_weight' => 1200,
            'cargo_volume' => 25,
            'goods_description' => 'Industrial goods',
            'currency' => 'USD',
            'status' => 'requested',
        ], $attributes)));
    }

    protected function createContainerShipment(array $attributes = []): B2BContainerShipment
    {
        return B2BContainerShipment::create((new B2BContainerShipment())->filterPersistable(array_merge([
            'freight_quote_id' => null,
            'shipment_id' => null,
            'forwarder_id' => null,
            'booking_number' => 'BK-' . random_int(10000, 99999),
            'bill_of_lading_number' => 'BL-' . random_int(10000, 99999),
            'container_number' => 'CONT-' . random_int(10000, 99999),
            'status' => 'booked',
            'source_provider' => 'Oceanic Forwarder',
            'tracking_reference' => 'CONT-' . random_int(10000, 99999),
        ], $attributes)));
    }

    protected function createFreightQuoteCost(B2BFreightQuote $quote, array $attributes = []): B2BFreightQuoteCost
    {
        return B2BFreightQuoteCost::create((new B2BFreightQuoteCost())->filterPersistable(array_merge([
            'freight_quote_id' => $quote->id,
            'cost_type' => 'base_freight_cost',
            'description' => 'Base freight',
            'amount' => 100,
            'currency' => $quote->currency ?? 'USD',
            'exchange_rate_snapshot' => 1,
            'payer' => 'buyer',
            'is_billable' => true,
            'is_optional' => false,
            'sort_order' => 0,
        ], $attributes)));
    }

    protected function createFreightPricingRule(array $attributes = []): B2BFreightPricingRule
    {
        return B2BFreightPricingRule::create((new B2BFreightPricingRule())->filterPersistable(array_merge([
            'name' => 'Default Sea Rule',
            'freight_mode' => 'sea_freight',
            'service_type' => 'port_to_port',
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'container_type' => '40HC',
            'incoterm' => 'FOB',
            'base_price' => 500,
            'price_per_kg' => 0.10,
            'price_per_cbm' => 2.5,
            'fuel_surcharge_percent' => 5,
            'platform_fee_percent' => 2,
            'platform_fee_fixed' => 15,
            'currency' => 'USD',
            'active' => true,
        ], $attributes)));
    }

    protected function createHsCode(array $attributes = []): B2BHsCode
    {
        return B2BHsCode::create((new B2BHsCode())->filterPersistable(array_merge([
            'hs_code' => '620342',
            'description' => 'Cotton trousers',
            'country' => 'Bangladesh',
            'duty_percent' => 10,
            'vat_gst_percent' => 15,
            'required_documents' => ['Commercial Invoice', 'Packing List'],
            'is_active' => true,
        ], $attributes)));
    }

    protected function createCustomsDocument(array $attributes = []): B2BCustomsDocument
    {
        return B2BCustomsDocument::create((new B2BCustomsDocument())->filterPersistable(array_merge([
            'uploaded_by' => null,
            'company_id' => null,
            'document_type' => 'commercial_invoice',
            'title' => 'Customs document',
            'file_path' => 'uploads/b2b_customs_documents/test.pdf',
        ], $attributes)));
    }
}
