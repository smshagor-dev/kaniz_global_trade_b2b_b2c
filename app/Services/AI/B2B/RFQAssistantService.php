<?php

namespace App\Services\AI\B2B;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use RuntimeException;

class RFQAssistantService
{
    public function __construct(
        protected AIRequestService $requestService,
        protected AIPromptService $promptService
    ) {
    }

    public function suggest(array $input, ?User $user = null, ?int $companyId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

        if ($title === '' && $description === '') {
            throw new RuntimeException('Title or description is required for RFQ assistance.');
        }

        $categoryName = null;
        if (!empty($input['category_id'])) {
            $categoryName = Category::query()->where('id', $input['category_id'])->value('name');
        }

        $productName = null;
        if (!empty($input['product_id'])) {
            $productName = Product::query()->where('id', $input['product_id'])->value('name');
        }

        $rendered = $this->promptService->render('b2b_rfq_assistant', [
            'title' => $title,
            'description' => $description,
            'category' => $categoryName,
            'product' => $productName,
            'quantity' => $input['quantity'] ?? null,
            'unit' => $input['unit'] ?? null,
            'target_price' => $input['target_price'] ?? null,
            'currency' => $input['currency'] ?? null,
            'destination_country' => $input['destination_country'] ?? null,
            'destination_city' => $input['destination_city'] ?? null,
            'incoterm' => $input['incoterm'] ?? null,
        ]);

        $result = $this->requestService->request([
            'module' => 'b2b_rfq_assistant',
            'system_prompt' => $rendered['system_prompt'],
            'prompt' => $rendered['user_prompt'],
            'user' => $user,
            'company_id' => $companyId,
            'metadata' => [
                'category_id' => $input['category_id'] ?? null,
                'product_id' => $input['product_id'] ?? null,
            ],
        ]);

        $decoded = json_decode($result['content'], true);
        if (!is_array($decoded)) {
            throw new RuntimeException('RFQ assistant returned an invalid response.');
        }

        return array_merge($decoded, [
            '_meta' => [
                'provider' => $result['provider'],
                'model' => $result['model'],
                'request_id' => $result['request_id'],
            ],
        ]);
    }
}
