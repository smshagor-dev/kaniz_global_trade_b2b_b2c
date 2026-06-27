<?php

namespace Tests\Feature\B2B;

use App\Jobs\ReindexSearchChunkJob;
use App\Jobs\SyncSearchDocumentJob;
use App\Models\BusinessSetting;
use App\Models\B2BCompany;
use App\Models\Product;
use App\Models\SearchDocument;
use App\Models\SearchIndexingFailure;
use App\Models\SearchIndexingRun;
use App\Services\Search\SearchEngineInterface;
use App\Services\Search\SearchManager;
use App\Services\Search\SearchService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

class EnterpriseSearchTest extends B2BFeatureTestCase
{
    public function test_database_search_finds_products_and_supports_autocomplete(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);
        BusinessSetting::updateOrCreate(['type' => 'search_index_name'], ['value' => 'marketplace']);

        $seller = $this->createSellerUser();
        $category = $this->createCategory(['name' => 'Industrial Tools', 'slug' => 'industrial-tools']);
        $product = $this->createProduct($seller, $category, [
            'name' => 'Hydraulic Press',
            'slug' => 'hydraulic-press',
            'wholesale_product' => 0,
        ]);

        app(SearchService::class)->indexModel($product->fresh());

        $response = app(SearchService::class)->search('Hydraulic', ['types' => ['product']]);
        $autocomplete = app(SearchService::class)->autocomplete('Hydr', ['types' => ['product']]);

        $this->assertSame('database', $response['provider']);
        $this->assertGreaterThanOrEqual(1, $response['total']);
        $this->assertSame('Hydraulic Press', $response['results'][0]['title']);
        $this->assertSame('Hydraulic Press', $autocomplete['suggestions'][0]['title']);
    }

    public function test_search_discovers_supplier_hs_code_port_and_forwarder(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $supplierUser = $this->createSellerUser();
        $company = $this->createCompany($supplierUser, [
            'company_name' => 'Atlas Metals',
            'company_type' => 'supplier',
            'public_profile_enabled' => 1,
            'featured_supplier' => 1,
        ]);
        $port = $this->createPort(['name' => 'Chittagong Port', 'code' => 'CGP', 'country' => 'Bangladesh']);
        $forwarder = $this->createFreightForwarder(['name' => 'Maersk Global Forwarding', 'driver' => 'maersk', 'is_active' => 1]);
        $hsCode = $this->createHsCode(['hs_code' => '730890', 'description' => 'Iron or steel structures', 'country' => 'Bangladesh']);

        $searchService = app(SearchService::class);
        foreach ([$company->fresh(), $port->fresh(), $forwarder->fresh(), $hsCode->fresh()] as $model) {
            $searchService->indexModel($model);
        }

        $companyResults = $searchService->search('Atlas', ['types' => ['company']]);
        $portResults = $searchService->search('Chittagong', ['types' => ['port']]);
        $forwarderResults = $searchService->search('Maersk', ['types' => ['freight_forwarder']]);
        $hsCodeResults = $searchService->search('730890', ['types' => ['hs_code']]);

        $this->assertSame('Atlas Metals', $companyResults['results'][0]['title']);
        $this->assertSame('Chittagong Port', $portResults['results'][0]['title']);
        $this->assertSame('Maersk Global Forwarding', $forwarderResults['results'][0]['title']);
        $this->assertSame('730890', $hsCodeResults['results'][0]['title']);
    }

    public function test_private_purchase_orders_are_hidden_from_public_search_but_visible_to_authorized_user(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $buyerUser = $this->createUser();
        $supplierUser = $this->createSellerUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_name' => 'Buyer One']);
        $supplierCompany = $this->createCompany($supplierUser, ['company_name' => 'Supplier One', 'company_type' => 'supplier', 'public_profile_enabled' => 1]);
        $rfq = $this->createRfq($buyerCompany, $buyerUser, ['title' => 'Need copper coils']);
        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser, ['message' => 'Copper coil offer']);
        $purchaseOrder = $this->createPurchaseOrder($quotation, ['po_number' => 'PO-SEARCH-1001']);

        app(SearchService::class)->indexModel($purchaseOrder->fresh());

        $publicResults = app(SearchService::class)->search('PO-SEARCH-1001', ['types' => ['purchase_order']]);
        $buyerResults = app(SearchService::class)->search('PO-SEARCH-1001', ['types' => ['purchase_order'], 'include_private' => true], $buyerUser);

        $this->assertSame(0, $publicResults['total']);
        $this->assertSame(1, $buyerResults['total']);
        $this->assertSame('PO-SEARCH-1001', $buyerResults['results'][0]['title']);
    }

    public function test_unconfigured_external_provider_falls_back_to_database_search(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'opensearch']);

        $seller = $this->createSellerUser();
        $product = $this->createProduct($seller, null, [
            'name' => 'Fallback Search Drill',
            'slug' => 'fallback-search-drill',
            'wholesale_product' => 0,
        ]);

        app(SearchService::class)->indexModel($product->fresh());

        $response = app(SearchService::class)->search('Fallback Search Drill', ['types' => ['product']]);

        $this->assertSame('database', $response['provider']);
        $this->assertSame('Fallback Search Drill', $response['results'][0]['title']);
    }

    public function test_reindex_command_processes_products_in_chunks(): void
    {
        config(['search.reindex.max_sync_chunks' => 10000]);
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $seller = $this->createSellerUser();
        $category = $this->createCategory(['name' => 'Chunked Products', 'slug' => 'chunked-products']);
        $totalProducts = Product::query()->count() + 120;

        for ($index = 1; $index <= 120; $index++) {
            $this->createProduct($seller, $category, [
                'name' => 'Chunk Product ' . $index,
                'slug' => 'chunk-product-' . $index,
                'wholesale_product' => 0,
            ]);
        }

        Artisan::call('search:reindex', [
            '--entity' => Product::class,
            '--chunk' => 50,
        ]);

        $run = SearchIndexingRun::query()->latest('id')->first();

        $this->assertSame($totalProducts, $run->total_models);
        $this->assertNotNull($run);
        $this->assertSame('completed', $run->status);
        $this->assertSame((int) ceil($totalProducts / 50), $run->processed_chunks);
        $this->assertSame(120, SearchDocument::query()->where('title', 'like', 'Chunk Product %')->count());
        $this->assertStringContainsString('chunk ' . $run->processed_chunks, Artisan::output());
    }

    public function test_reindex_command_targets_supplier_entities_only(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $seller = $this->createSellerUser();
        $company = $this->createCompany($seller, [
            'company_name' => 'Supplier Search Target',
            'company_type' => 'supplier',
            'public_profile_enabled' => 1,
        ]);
        $existingProductDocuments = SearchDocument::query()->where('model_type', Product::class)->count();
        $this->createProduct($seller, null, [
            'name' => 'Should Stay Unindexed',
            'slug' => 'should-stay-unindexed',
            'wholesale_product' => 0,
        ]);

        Artisan::call('search:reindex', [
            '--entity' => 'suppliers',
        ]);

        $this->assertSame(1, SearchDocument::query()->where('title', 'Supplier Search Target')->count());
        $this->assertSame($existingProductDocuments, SearchDocument::query()->where('model_type', Product::class)->count());
        $this->assertSame('Supplier Search Target', SearchDocument::query()->where('title', 'Supplier Search Target')->first()->title);
    }

    public function test_reindex_command_dry_run_reports_counts_without_indexing(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $seller = $this->createSellerUser();
        $category = $this->createCategory(['name' => 'Dry Run Category', 'slug' => 'dry-run-category']);
        $this->createProduct($seller, $category, ['name' => 'Dry Run Product One', 'slug' => 'dry-run-product-one', 'wholesale_product' => 0]);
        $this->createProduct($seller, $category, ['name' => 'Dry Run Product Two', 'slug' => 'dry-run-product-two', 'wholesale_product' => 0]);
        $expectedTotal = Product::query()->count();
        $existingDocuments = SearchDocument::count();

        Artisan::call('search:reindex', [
            '--entity' => Product::class,
            '--chunk' => 50,
            '--dry-run' => true,
        ]);

        $run = SearchIndexingRun::query()->latest('id')->first();

        $this->assertSame($existingDocuments, SearchDocument::count());
        $this->assertNotNull($run);
        $this->assertSame('dry_run', $run->status);
        $this->assertSame($expectedTotal, $run->total_models);
        $this->assertStringContainsString('Dry run complete', Artisan::output());
    }

    public function test_reindex_command_queue_mode_dispatches_chunk_jobs(): void
    {
        Queue::fake();
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $seller = $this->createSellerUser();
        $category = $this->createCategory(['name' => 'Queued Products', 'slug' => 'queued-products']);
        $expectedTotal = Product::query()->count() + 3;

        for ($index = 1; $index <= 3; $index++) {
            $this->createProduct($seller, $category, [
                'name' => 'Queued Product ' . $index,
                'slug' => 'queued-product-' . $index,
                'wholesale_product' => 0,
            ]);
        }

        Artisan::call('search:reindex', [
            '--entity' => Product::class,
            '--chunk' => 2,
            '--queue' => true,
        ]);

        $run = SearchIndexingRun::query()->latest('id')->first();

        Queue::assertPushed(ReindexSearchChunkJob::class, (int) ceil($expectedTotal / 2));
        Queue::assertPushed(ReindexSearchChunkJob::class, fn (ReindexSearchChunkJob $job) => $job->modelClass() === Product::class);
        $this->assertNotNull($run);
        $this->assertSame('queued', $run->status);
        $this->assertSame((int) ceil($expectedTotal / 2), $run->queued_chunks);
    }

    public function test_reindex_command_logs_failures_when_indexing_breaks(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $seller = $this->createSellerUser();
        $product = $this->createProduct($seller, null, [
            'name' => 'Failure Product',
            'slug' => 'failure-product',
            'wholesale_product' => 0,
        ]);

        $this->app->instance(SearchManager::class, new class extends SearchManager {
            public function driver(?string $provider = null): SearchEngineInterface
            {
                return new class implements SearchEngineInterface {
                    public function search(array $payload): array
                    {
                        return ['hits' => [], 'total' => 0];
                    }

                    public function autocomplete(array $payload): array
                    {
                        return ['hits' => [], 'total' => 0];
                    }

                    public function index(string $indexName, string $documentId, array $document): void
                    {
                        throw new \RuntimeException('Simulated indexing failure');
                    }

                    public function bulkIndex(string $indexName, array $documents): void
                    {
                        throw new \RuntimeException('Simulated bulk indexing failure');
                    }

                    public function delete(string $indexName, string $documentId): void
                    {
                    }

                    public function createIndex(string $indexName, array $schema = []): void
                    {
                    }

                    public function deleteIndex(string $indexName): void
                    {
                    }

                    public function health(string $indexName): array
                    {
                        return ['ok' => true, 'provider' => 'database', 'index' => $indexName];
                    }
                };
            }

            public function activeProvider(): string
            {
                return 'database';
            }

            public function resilientDriver(?string $provider = null): SearchEngineInterface
            {
                return $this->driver($provider);
            }

            public function indexName(): string
            {
                return 'marketplace';
            }
        });

        Artisan::call('search:reindex', [
            '--entity' => Product::class,
        ]);

        $failure = SearchIndexingFailure::query()->latest('id')->first();

        $this->assertNotNull($failure);
        $this->assertSame(Product::class, $failure->model_type);
        $this->assertSame($product->id, (int) $failure->model_id);
        $this->assertSame('index', $failure->operation);
        $this->assertStringContainsString('Simulated indexing failure', $failure->message);
    }

    public function test_retry_failed_command_dispatches_unresolved_failures(): void
    {
        Queue::fake();
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $seller = $this->createSellerUser();
        $product = $this->createProduct($seller, null, [
            'name' => 'Retry Product',
            'slug' => 'retry-product',
            'wholesale_product' => 0,
        ]);
        $run = SearchIndexingRun::create([
            'entity' => 'products',
            'provider' => 'database',
            'chunk_size' => 100,
            'is_queue' => true,
            'is_dry_run' => false,
            'status' => 'queued',
            'total_models' => 1,
            'started_at' => now(),
        ]);

        SearchIndexingFailure::create([
            'run_id' => $run->id,
            'index_name' => 'marketplace',
            'model_type' => Product::class,
            'model_id' => $product->id,
            'operation' => 'index',
            'provider' => 'database',
            'message' => 'Temporary failure',
            'payload' => [],
            'failed_at' => now(),
            'attempts' => 1,
        ]);

        Artisan::call('search:retry-failed', [
            '--queue' => true,
            '--run' => $run->id,
        ]);

        Queue::assertPushed(SyncSearchDocumentJob::class, 1);
        Queue::assertPushed(SyncSearchDocumentJob::class, fn (SyncSearchDocumentJob $job) => $job->modelClass() === Product::class && $job->modelId() === $product->id && $job->runId() === $run->id);
    }

    public function test_reindex_command_can_resume_a_paused_run(): void
    {
        config(['search.reindex.max_sync_chunks' => 1]);
        BusinessSetting::updateOrCreate(['type' => 'search_provider'], ['value' => 'database']);

        $totalCompanies = B2BCompany::query()->count() + 3;

        for ($index = 1; $index <= 3; $index++) {
            $this->createCompany($this->createSellerUser(), [
                'company_name' => 'Resume Company ' . $index,
                'company_type' => 'supplier',
                'public_profile_enabled' => 1,
            ]);
        }

        $chunkSize = max($totalCompanies - 1, 1);

        Artisan::call('search:reindex', [
            '--entity' => B2BCompany::class,
            '--chunk' => $chunkSize,
        ]);

        $firstRun = SearchIndexingRun::query()->latest('id')->first();

        $this->assertSame('paused', $firstRun->status);
        $this->assertSame($chunkSize, $firstRun->processed_models);

        Artisan::call('search:reindex', [
            '--entity' => B2BCompany::class,
            '--chunk' => $chunkSize,
            '--resume' => true,
            '--run' => $firstRun->id,
        ]);

        $this->assertSame(3, SearchDocument::query()->where('title', 'like', 'Resume Company %')->count());
        $this->assertSame('completed', $firstRun->fresh()->status);
    }
}
