<?php

namespace App\Services\AI;

use App\Models\AIProviderSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GlobalSearchAIService
{
    public function __construct(
        protected AIManager $manager,
        protected AIRequestService $requestService
    ) {
    }

    public function analyzeIntent(string $query, ?User $user = null): array
    {
        $query = trim($query);

        if ($query === '') {
            return $this->fallbackIntent($query);
        }

        if (!$this->manager->defaultProvider()) {
            return array_merge($this->fallbackIntent($query), [
                'ai_available' => false,
                'message' => 'AI mode is not configured.',
            ]);
        }

        try {
            $result = $this->requestService->request([
                'module' => 'global_search_ai_mode',
                'system_prompt' => 'You convert global trade marketplace search queries into JSON only.',
                'prompt' => implode("\n", [
                    'Return strict JSON with these keys:',
                    'intent, product, quantity, destination, search_types, search_filters, suggested_categories, suggested_actions, hs_code, incoterm, keywords, summary.',
                    'search_types must contain only marketplace search types such as product, wholesale_product, company, brand, category, rfq, hs_code, port, country, city, freight_forwarder, shipment, container_shipment.',
                    'search_filters must be a flat object with string, numeric, or array values.',
                    'suggested_actions must be short action labels like create_rfq, view_suppliers, compare_products, check_hs_code, review_freight.',
                    'If the request is not trade-related, still return a useful marketplace interpretation.',
                    'User query: ' . $query,
                ]),
                'user' => $user,
                'metadata' => ['feature' => 'global_search_ai_mode'],
            ]);

            $decoded = $this->decodeJson($result['content']);

            return array_merge($this->fallbackIntent($query), [
                'ai_available' => true,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'summary' => (string) ($decoded['summary'] ?? ''),
                'intent' => (string) ($decoded['intent'] ?? 'general_search'),
                'product' => (string) ($decoded['product'] ?? ''),
                'quantity' => $decoded['quantity'] ?? null,
                'destination' => (string) ($decoded['destination'] ?? ''),
                'search_types' => $this->sanitizeSearchTypes((array) ($decoded['search_types'] ?? [])),
                'search_filters' => $this->sanitizeFilters((array) ($decoded['search_filters'] ?? [])),
                'suggested_categories' => array_values(array_filter((array) ($decoded['suggested_categories'] ?? []))),
                'suggested_actions' => array_values(array_filter((array) ($decoded['suggested_actions'] ?? []))),
                'hs_code' => (string) ($decoded['hs_code'] ?? ''),
                'incoterm' => (string) ($decoded['incoterm'] ?? ''),
                'keywords' => array_values(array_filter((array) ($decoded['keywords'] ?? []))),
            ]);
        } catch (\Throwable $throwable) {
            return array_merge($this->fallbackIntent($query), [
                'ai_available' => false,
                'message' => 'AI mode is temporarily unavailable.',
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    public function analyzeImage(UploadedFile $image, ?User $user = null): array
    {
        $providers = $this->visionProviders();

        if ($providers === []) {
            throw new RuntimeException('AI image search is not configured.');
        }

        $maxSizeKb = (int) get_setting('global_search_image_max_upload_kb', 4096);
        if ($image->getSize() > ($maxSizeKb * 1024)) {
            throw new RuntimeException('The uploaded image exceeds the allowed size.');
        }

        $path = $image->store('tmp/global-search-images', 'local');

        try {
            $binary = Storage::disk('local')->get($path);
            $mimeType = $image->getMimeType() ?: 'image/jpeg';
            $base64 = base64_encode($binary);
            $imageHash = hash('sha256', $binary);

            $result = $this->requestService->request([
                'module' => 'global_search_image_search',
                'system_prompt' => 'You analyze marketplace product images and return JSON only.',
                'prompt' => implode("\n", [
                    'Return strict JSON with these keys:',
                    'product_name, category, color, material, keywords, possible_hs_code, supplier_category, summary.',
                    'keywords must be an array of short strings suitable for marketplace search.',
                    'If the product is uncertain, return your best estimate and keep the summary cautious.',
                ]),
                'image' => [
                    'mime_type' => $mimeType,
                    'base64' => $base64,
                ],
                'image_hash' => $imageHash,
                'provider_setting_id' => $providers[0]->id,
                'user' => $user,
                'metadata' => ['feature' => 'global_search_image_search'],
            ]);

            $decoded = $this->decodeJson($result['content']);

            return [
                'provider' => $result['provider'],
                'model' => $result['model'],
                'summary' => (string) ($decoded['summary'] ?? ''),
                'product_name' => (string) ($decoded['product_name'] ?? ''),
                'category' => (string) ($decoded['category'] ?? ''),
                'color' => (string) ($decoded['color'] ?? ''),
                'material' => (string) ($decoded['material'] ?? ''),
                'keywords' => array_values(array_filter((array) ($decoded['keywords'] ?? []))),
                'possible_hs_code' => (string) ($decoded['possible_hs_code'] ?? ''),
                'supplier_category' => (string) ($decoded['supplier_category'] ?? ''),
                'request_id' => $result['request_id'] ?? null,
            ];
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function visionProviders(): array
    {
        return array_values(array_filter(
            $this->manager->activeProviders(),
            fn (AIProviderSetting $provider) => $this->supportsVision($provider)
        ));
    }

    protected function supportsVision(AIProviderSetting $provider): bool
    {
        if (!$provider->api_key) {
            return false;
        }

        $configured = data_get($provider->settings, 'supports_vision');
        if ($configured !== null) {
            return (bool) $configured;
        }

        return in_array($provider->provider, ['gemini', 'openai'], true);
    }

    protected function decodeJson(string $content): array
    {
        $content = trim($content);

        if (Str::startsWith($content, '```')) {
            $content = preg_replace('/^```(?:json)?|```$/m', '', $content) ?: $content;
            $content = trim($content);
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('The AI provider returned invalid JSON.');
        }

        return $decoded;
    }

    protected function fallbackIntent(string $query): array
    {
        preg_match('/\b(\d{2,})\b/', $query, $quantityMatch);

        return [
            'summary' => '',
            'intent' => Str::contains(Str::lower($query), ['ship', 'freight', 'logistics', 'container'])
                ? 'logistics'
                : (Str::contains(Str::lower($query), ['rfq', 'source', 'supplier', 'wholesale', 'bulk']) ? 'b2b_sourcing' : 'general_search'),
            'product' => '',
            'quantity' => isset($quantityMatch[1]) ? (int) $quantityMatch[1] : null,
            'destination' => '',
            'search_types' => [],
            'search_filters' => [],
            'suggested_categories' => [],
            'suggested_actions' => Str::contains(Str::lower($query), ['rfq', 'source', 'supplier', 'bulk'])
                ? ['create_rfq', 'view_suppliers']
                : ['view_products'],
            'hs_code' => '',
            'incoterm' => '',
            'keywords' => [],
        ];
    }

    protected function sanitizeSearchTypes(array $types): array
    {
        $allowed = [
            'product',
            'wholesale_product',
            'company',
            'brand',
            'category',
            'rfq',
            'hs_code',
            'port',
            'country',
            'city',
            'freight_forwarder',
            'shipment',
            'container_shipment',
        ];

        return array_values(array_intersect($allowed, array_map('strval', $types)));
    }

    protected function sanitizeFilters(array $filters): array
    {
        return collect($filters)
            ->mapWithKeys(function ($value, $key) {
                if (is_array($value)) {
                    return [(string) $key => array_values(array_filter($value, fn ($item) => is_scalar($item) && $item !== ''))];
                }

                return is_scalar($value) && $value !== ''
                    ? [(string) $key => $value]
                    : [];
            })
            ->all();
    }
}
