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

class SearchModelRegistry
{
    public const ENTITY_MAP = [
        'all' => [
            Product::class,
            B2BCompany::class,
            Brand::class,
            Category::class,
            B2BRfq::class,
            B2BPurchaseOrder::class,
            B2BProformaInvoice::class,
            B2BTradeDocument::class,
            B2BHsCode::class,
            B2BPort::class,
            B2BFreightForwarder::class,
            B2BShipment::class,
            B2BContainerShipment::class,
            Country::class,
            City::class,
        ],
        'products' => [
            Product::class,
            Brand::class,
            Category::class,
        ],
        'suppliers' => [
            B2BCompany::class,
        ],
        'rfqs' => [
            B2BRfq::class,
            B2BPurchaseOrder::class,
            B2BProformaInvoice::class,
            B2BTradeDocument::class,
        ],
        'freight' => [
            B2BHsCode::class,
            B2BPort::class,
            B2BFreightForwarder::class,
            B2BShipment::class,
            B2BContainerShipment::class,
        ],
    ];

    public static function models(): array
    {
        return self::ENTITY_MAP['all'];
    }

    public static function entityOptions(): array
    {
        return array_keys(self::ENTITY_MAP);
    }

    public static function resolve(?string $entity): array
    {
        if (!$entity || $entity === 'all') {
            return self::models();
        }

        if (isset(self::ENTITY_MAP[$entity])) {
            return self::ENTITY_MAP[$entity];
        }

        if (in_array($entity, self::models(), true)) {
            return [$entity];
        }

        return [];
    }
}
