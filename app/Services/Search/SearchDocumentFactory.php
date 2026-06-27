<?php

namespace App\Services\Search;

use App\Models\B2BCompany;
use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BHsCode;
use App\Models\B2BPort;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BRfq;
use App\Models\B2BShipment;
use App\Models\B2BTradeDocument;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SearchDocumentFactory
{
    public function build(Model $model): array
    {
        return match (get_class($model)) {
            Product::class => $this->product($model),
            B2BCompany::class => $this->company($model),
            Brand::class => $this->brand($model),
            Category::class => $this->category($model),
            B2BRfq::class => $this->rfq($model),
            B2BPurchaseOrder::class => $this->purchaseOrder($model),
            B2BProformaInvoice::class => $this->proformaInvoice($model),
            B2BTradeDocument::class => $this->tradeDocument($model),
            B2BHsCode::class => $this->hsCode($model),
            B2BPort::class => $this->port($model),
            B2BFreightForwarder::class => $this->freightForwarder($model),
            B2BShipment::class => $this->shipment($model),
            B2BContainerShipment::class => $this->containerShipment($model),
            Country::class => $this->country($model),
            City::class => $this->city($model),
            default => [],
        };
    }

    protected function product(Product $product): array
    {
        if (!(int) $product->approved || !(int) $product->published) {
            return [];
        }

        $type = (int) $product->wholesale_product === 1 ? 'wholesale_product' : 'product';
        $name = $product->name ?: $product->getTranslation('name');
        $stockSku = $product->stocks()->pluck('sku')->implode(' ');
        $rating = round((float) $product->reviews()->avg('rating'), 2);
        $sales = (float) $product->orderDetails()->sum('quantity');
        $supplierCompany = $product->publicSupplierCompany()->first();

        return $this->document($type, $product, [
            'title' => $name,
            'subtitle' => optional($supplierCompany)->company_name,
            'summary' => strip_tags((string) $product->description),
            'url' => route('product', $product->slug),
            'search_text' => $this->implode([
                $name,
                $product->tags,
                $stockSku,
                optional($product->brand)->name,
                optional($product->main_category)->name,
                $product->product_translations->pluck('name')->implode(' '),
            ]),
            'keywords' => $this->implode([$stockSku, $product->tags, optional($product->brand)->name]),
            'filters' => [
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'country' => optional($supplierCompany)->country,
                'supplier_id' => optional($supplierCompany)->id,
                'verified' => (bool) optional($supplierCompany)->verified_supplier_badge,
                'ready_to_ship' => (bool) ($product->current_stock > 0),
                'price' => (float) $product->unit_price,
                'moq' => (int) ($product->min_qty ?? 1),
            ],
            'metadata' => [
                'slug' => $product->slug,
                'supplier_company_id' => optional($supplierCompany)->id,
                'supplier_company_name' => optional($supplierCompany)->company_name,
                'sku' => $stockSku,
            ],
            'visibility' => 'public',
            'rank_popularity' => (float) $product->wishlists()->count(),
            'rank_sales' => $sales,
            'rank_verified' => optional($supplierCompany)->verified_supplier_badge ? 1 : 0,
            'rank_featured' => optional($supplierCompany)->featured_supplier ? 1 : 0,
            'rank_supplier_score' => (float) (optional($supplierCompany)->profile_score ?? 0),
            'rank_rating' => $rating,
            'rank_response_rate' => (float) (optional($supplierCompany)->response_rate ?? 0),
            'rank_recency' => $this->recencyScore($product->updated_at),
        ]);
    }

    protected function company(B2BCompany $company): array
    {
        if ($company->verification_status !== 'approved' || !$company->public_profile_enabled) {
            return [];
        }

        return $this->document('company', $company, [
            'entity_subtype' => (string) $company->company_type,
            'title' => $company->company_name,
            'subtitle' => Str::title((string) $company->company_type),
            'summary' => $company->description,
            'url' => $company->public_slug ? route('b2b.suppliers.show', $company->public_slug) : null,
            'search_text' => $this->implode([
                $company->company_name,
                $company->legal_name,
                $company->description,
                $company->country,
                $company->city,
                $company->website,
                $company->main_markets,
                $company->business_scope,
            ]),
            'keywords' => $this->implode([$company->company_type, $company->registration_number, $company->tax_number]),
            'filters' => [
                'country' => $company->country,
                'company_type' => $company->company_type,
                'verified' => (bool) $company->verified_supplier_badge,
                'featured' => (bool) $company->featured_supplier,
                'factory' => filled($company->factory_location),
                'response_rate' => (float) ($company->response_rate ?? 0),
                'certifications' => $company->certifications()->where('verification_status', 'approved')->count(),
            ],
            'metadata' => [
                'public_slug' => $company->public_slug,
            ],
            'visibility' => 'public',
            'rank_verified' => $company->verified_supplier_badge ? 1 : 0,
            'rank_featured' => $company->featured_supplier ? 1 : 0,
            'rank_supplier_score' => (float) ($company->profile_score ?? 0),
            'rank_trade_volume' => (float) ($company->supplierPurchaseOrders()->count() + $company->buyerPurchaseOrders()->count()),
            'rank_response_rate' => (float) ($company->response_rate ?? 0),
            'rank_recency' => $this->recencyScore($company->updated_at),
        ]);
    }

    protected function brand(Brand $brand): array
    {
        return $this->document('brand', $brand, [
            'title' => $brand->name,
            'summary' => $brand->meta_description,
            'url' => route('products.brand', $brand->slug),
            'search_text' => $this->implode([$brand->name, $brand->meta_title, $brand->meta_description]),
            'filters' => [],
            'metadata' => ['slug' => $brand->slug],
            'visibility' => 'public',
            'rank_popularity' => (float) $brand->products()->count(),
            'rank_recency' => $this->recencyScore($brand->updated_at),
        ]);
    }

    protected function category(Category $category): array
    {
        return $this->document('category', $category, [
            'title' => $category->name,
            'summary' => $category->meta_description,
            'url' => route('products.category', $category->slug),
            'search_text' => $this->implode([$category->name, $category->slug, $category->category_translations->pluck('name')->implode(' ')]),
            'filters' => ['parent_id' => $category->parent_id],
            'metadata' => ['slug' => $category->slug, 'level' => $category->level],
            'visibility' => 'public',
            'rank_popularity' => (float) $category->products()->count(),
            'rank_recency' => $this->recencyScore($category->updated_at),
        ]);
    }

    protected function rfq(B2BRfq $rfq): array
    {
        return $this->document('rfq', $rfq, [
            'title' => $rfq->title,
            'subtitle' => optional($rfq->company)->company_name,
            'summary' => $rfq->description,
            'url' => auth()->check() ? route('b2b.rfqs.show', $rfq->id) : null,
            'search_text' => $this->implode([
                $rfq->title,
                $rfq->description,
                optional($rfq->category)->name,
                optional($rfq->product)->name,
                $rfq->destination_country,
                $rfq->destination_city,
            ]),
            'filters' => [
                'status' => $rfq->status,
                'country' => $rfq->destination_country,
                'deadline' => optional($rfq->expires_at)->toDateString(),
                'budget' => (float) $rfq->target_price,
            ],
            'metadata' => [
                'buyer_company_id' => $rfq->b2b_company_id,
                'supplier_company_id' => $rfq->supplier_company_id,
            ],
            'visibility' => 'restricted',
            'rank_trade_volume' => (float) $rfq->quotations()->count(),
            'rank_recency' => $this->recencyScore($rfq->updated_at),
        ]);
    }

    protected function purchaseOrder(B2BPurchaseOrder $purchaseOrder): array
    {
        return $this->document('purchase_order', $purchaseOrder, [
            'title' => $purchaseOrder->po_number,
            'subtitle' => optional($purchaseOrder->supplierCompany)->company_name,
            'summary' => $purchaseOrder->notes,
            'url' => null,
            'search_text' => $this->implode([
                $purchaseOrder->po_number,
                $purchaseOrder->notes,
                optional($purchaseOrder->buyerCompany)->company_name,
                optional($purchaseOrder->supplierCompany)->company_name,
            ]),
            'filters' => [
                'status' => $purchaseOrder->status,
            ],
            'metadata' => [
                'buyer_company_id' => $purchaseOrder->buyer_company_id,
                'supplier_company_id' => $purchaseOrder->supplier_company_id,
                'buyer_user_id' => $purchaseOrder->buyer_user_id,
                'supplier_user_id' => $purchaseOrder->supplier_user_id,
            ],
            'visibility' => 'private',
            'rank_trade_volume' => (float) $purchaseOrder->total_amount,
            'rank_recency' => $this->recencyScore($purchaseOrder->updated_at),
        ]);
    }

    protected function proformaInvoice(B2BProformaInvoice $invoice): array
    {
        return $this->document('invoice', $invoice, [
            'title' => $invoice->invoice_number,
            'subtitle' => optional($invoice->supplierCompany)->company_name,
            'summary' => $invoice->notes,
            'url' => null,
            'search_text' => $this->implode([
                $invoice->invoice_number,
                $invoice->notes,
                optional($invoice->buyerCompany)->company_name,
                optional($invoice->supplierCompany)->company_name,
            ]),
            'filters' => ['status' => $invoice->status],
            'metadata' => [
                'buyer_company_id' => $invoice->buyer_company_id,
                'supplier_company_id' => $invoice->supplier_company_id,
                'buyer_user_id' => $invoice->buyer_user_id,
                'supplier_user_id' => $invoice->supplier_user_id,
            ],
            'visibility' => 'private',
            'rank_trade_volume' => (float) ($invoice->grand_total ?? 0),
            'rank_recency' => $this->recencyScore($invoice->updated_at),
        ]);
    }

    protected function tradeDocument(B2BTradeDocument $document): array
    {
        return $this->document('trade_document', $document, [
            'title' => $document->title,
            'subtitle' => $document->document_type,
            'summary' => $document->notes,
            'url' => null,
            'search_text' => $this->implode([$document->title, $document->document_type, $document->notes]),
            'filters' => ['document_type' => $document->document_type],
            'metadata' => [
                'company_id' => $document->company_id,
                'documentable_type' => $document->documentable_type,
                'documentable_id' => $document->documentable_id,
            ],
            'visibility' => 'restricted',
            'rank_recency' => $this->recencyScore($document->updated_at),
        ]);
    }

    protected function hsCode(B2BHsCode $code): array
    {
        return $this->document('hs_code', $code, [
            'title' => (string) $code->hs_code,
            'subtitle' => $code->country,
            'summary' => $code->description,
            'url' => null,
            'search_text' => $this->implode([$code->hs_code, $code->description, $code->country, $code->restrictions]),
            'filters' => ['country' => $code->country],
            'metadata' => ['dangerous_goods' => (bool) $code->is_dangerous_goods],
            'visibility' => 'public',
            'rank_recency' => $this->recencyScore($code->updated_at),
        ]);
    }

    protected function port(B2BPort $port): array
    {
        return $this->document('port', $port, [
            'title' => $port->name,
            'subtitle' => $port->code ?: $port->unlocode,
            'summary' => $this->implode([$port->country, $port->city, $port->port_type]),
            'url' => null,
            'search_text' => $this->implode([$port->name, $port->code, $port->unlocode, $port->country, $port->city]),
            'filters' => ['country' => $port->country, 'city' => $port->city],
            'metadata' => ['port_type' => $port->port_type],
            'visibility' => 'public',
            'rank_recency' => $this->recencyScore($port->updated_at),
        ]);
    }

    protected function freightForwarder(B2BFreightForwarder $forwarder): array
    {
        if (!$forwarder->is_active) {
            return [];
        }

        return $this->document('freight_forwarder', $forwarder, [
            'title' => $forwarder->name,
            'subtitle' => $forwarder->driver,
            'summary' => $this->implode([$forwarder->website, $forwarder->provider_type]),
            'url' => null,
            'search_text' => $this->implode([
                $forwarder->name,
                $forwarder->driver,
                $forwarder->provider_type,
                implode(' ', (array) $forwarder->supported_modes),
                implode(' ', (array) $forwarder->supported_countries),
            ]),
            'filters' => [
                'mode' => $forwarder->supported_modes,
                'status' => 'active',
            ],
            'metadata' => ['driver' => $forwarder->driver],
            'visibility' => 'public',
            'rank_popularity' => (float) $forwarder->quotes()->count(),
            'rank_recency' => $this->recencyScore($forwarder->updated_at),
        ]);
    }

    protected function shipment(B2BShipment $shipment): array
    {
        return $this->document('shipment', $shipment, [
            'title' => $shipment->shipment_number,
            'subtitle' => $shipment->tracking_number,
            'summary' => $shipment->status,
            'url' => null,
            'search_text' => $this->implode([
                $shipment->shipment_number,
                $shipment->tracking_number,
                $shipment->carrier_reference,
                $shipment->current_location,
                $shipment->status,
            ]),
            'filters' => [
                'status' => $shipment->status,
                'mode' => $shipment->transport_mode,
            ],
            'metadata' => [
                'buyer_company_id' => $shipment->buyer_company_id,
                'supplier_company_id' => $shipment->supplier_company_id,
            ],
            'visibility' => 'restricted',
            'rank_recency' => $this->recencyScore($shipment->updated_at),
        ]);
    }

    protected function containerShipment(B2BContainerShipment $shipment): array
    {
        return $this->document('container_shipment', $shipment, [
            'title' => $shipment->container_number ?: $shipment->booking_number,
            'subtitle' => $shipment->bill_of_lading_number,
            'summary' => $shipment->status,
            'url' => null,
            'search_text' => $this->implode([
                $shipment->container_number,
                $shipment->booking_number,
                $shipment->bill_of_lading_number,
                $shipment->tracking_reference,
                $shipment->vessel_name,
                $shipment->voyage_number,
                $shipment->status,
            ]),
            'filters' => [
                'status' => $shipment->status,
                'container' => $shipment->container_number,
            ],
            'metadata' => [
                'buyer_company_id' => optional($shipment->freightQuote)->buyer_company_id,
                'supplier_company_id' => optional($shipment->freightQuote)->supplier_company_id,
            ],
            'visibility' => 'restricted',
            'rank_recency' => $this->recencyScore($shipment->updated_at),
        ]);
    }

    protected function country(Country $country): array
    {
        return $this->document('country', $country, [
            'title' => $country->name,
            'summary' => $country->code,
            'url' => null,
            'search_text' => $this->implode([$country->name, $country->code]),
            'filters' => ['code' => $country->code],
            'visibility' => 'public',
        ]);
    }

    protected function city(City $city): array
    {
        return $this->document('city', $city, [
            'title' => $city->name,
            'subtitle' => optional($city->country)->name,
            'summary' => optional($city->state)->name,
            'url' => null,
            'search_text' => $this->implode([$city->name, optional($city->country)->name, optional($city->state)->name]),
            'filters' => [
                'country_id' => $city->country_id,
                'state_id' => $city->state_id,
            ],
            'visibility' => 'public',
        ]);
    }

    protected function document(string $type, Model $model, array $attributes): array
    {
        $title = (string) ($attributes['title'] ?? '');

        return array_merge([
            'type' => $type,
            'entity_subtype' => $attributes['entity_subtype'] ?? null,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'title' => $title,
            'subtitle' => $attributes['subtitle'] ?? null,
            'summary' => Str::limit(strip_tags((string) ($attributes['summary'] ?? '')), 300),
            'url' => $attributes['url'] ?? null,
            'search_text' => $attributes['search_text'] ?? $title,
            'keywords' => $attributes['keywords'] ?? null,
            'filters' => $attributes['filters'] ?? [],
            'metadata' => $attributes['metadata'] ?? [],
            'visibility' => $attributes['visibility'] ?? 'public',
            'is_active' => true,
            'rank_exact' => 1,
            'rank_popularity' => (float) ($attributes['rank_popularity'] ?? 0),
            'rank_sales' => (float) ($attributes['rank_sales'] ?? 0),
            'rank_verified' => (float) ($attributes['rank_verified'] ?? 0),
            'rank_featured' => (float) ($attributes['rank_featured'] ?? 0),
            'rank_supplier_score' => (float) ($attributes['rank_supplier_score'] ?? 0),
            'rank_rating' => (float) ($attributes['rank_rating'] ?? 0),
            'rank_trade_volume' => (float) ($attributes['rank_trade_volume'] ?? 0),
            'rank_response_rate' => (float) ($attributes['rank_response_rate'] ?? 0),
            'rank_recency' => (float) ($attributes['rank_recency'] ?? 0),
            'rank_ai_score' => (float) ($attributes['rank_ai_score'] ?? 0),
        ], $attributes);
    }

    protected function recencyScore($date): float
    {
        if (!$date) {
            return 0;
        }

        $days = max(1, Carbon::parse($date)->diffInDays(now()));

        return round(365 / $days, 2);
    }

    protected function implode(array $parts): string
    {
        return trim(collect($parts)->filter(fn ($part) => filled($part))->implode(' '));
    }
}
