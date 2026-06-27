<?php

namespace App\Services;

use App\Services\AI\AILegacySettingsService;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use App\Models\Language;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function __construct(
        protected AIRequestService $requestService,
        protected AIPromptService $promptService,
        protected AILegacySettingsService $legacySettingsService
    ) {
    }

    public function productGenerateWithAI(array $data)
    {
        try {
            $this->legacySettingsService->sync();

            $productName = trim((string) ($data['product_name'] ?? ''));
            $section = $data['section'] ?? null;
            $language = $data['lang'] ?? default_language();
            $existingData = (array) ($data['existing_data'] ?? []);

            if ($productName === '') {
                return response()->json(['success' => false, 'message' => 'Product name is required']);
            }

            if (!$section) {
                return response()->json(['success' => false, 'message' => 'Section is required']);
            }

            $languageName = Language::where('code', $language)->value('name') ?? 'English';

            $fieldMap = [
                'basic-information' => [
                    'fields' => ['name'],
                    'prompt_fields' => 'name: a clean, attractive, SEO-friendly product title (max 100 characters)',
                    'language_target' => $languageName,
                ],
                'product-description' => [
                    'fields' => ['description'],
                    'prompt_fields' => 'description: 2-4 paragraphs, attractive, benefit-focused HTML',
                    'language_target' => $languageName,
                ],
                'product-seo-meta-tag' => [
                    'fields' => ['meta_title', 'meta_description', 'meta_keywords'],
                    'prompt_fields' => 'meta_title, meta_description, meta_keywords (SEO optimized)',
                    'language_target' => 'English',
                ],
                'product-configuration' => [
                    'fields' => ['unit', 'weight', 'min_qty', 'tags'],
                    'prompt_fields' => 'unit, weight (kg), min_qty, tags',
                    'language_target' => $languageName,
                ],
            ];

            if (!isset($fieldMap[$section])) {
                return response()->json(['success' => false, 'message' => 'Invalid section']);
            }

            $config = $fieldMap[$section];
            $rendered = $this->promptService->render('product_generation', [
                'product_name' => $productName,
                'language' => $config['language_target'],
                'prompt_fields' => $config['prompt_fields'],
                'existing_data' => $existingData !== [] ? json_encode($existingData) : '',
            ], 'product_add_edit_prompt');

            $result = $this->requestService->request([
                'module' => 'product_generation',
                'system_prompt' => $rendered['system_prompt'],
                'prompt' => $rendered['user_prompt'],
                'user' => auth()->user(),
                'metadata' => [
                    'section' => $section,
                    'language' => $language,
                ],
            ]);

            $text = trim((string) $result['content']);
            $text = preg_replace('/^```json\s*|\s*```$/m', '', $text);
            $text = preg_replace('/^```\s*|\s*```$/m', '', $text);
            $parsed = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
                Log::error('Invalid AI JSON response', [
                    'content' => $text,
                    'error' => json_last_error_msg(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid AI JSON response',
                ], 422);
            }

            $clean = [];
            foreach ($config['fields'] as $field) {
                $clean[$field] = $parsed[$field] ?? null;
            }

            return response()->json([
                'success' => true,
                'data' => $clean,
                'section' => $section,
                'language' => $language,
                'is_regenerated' => $existingData !== [],
                'tokens' => [
                    'prompt_tokens' => $result['prompt_tokens'],
                    'completion_tokens' => $result['completion_tokens'],
                    'total_tokens' => $result['total_tokens'],
                ],
                'provider' => $result['provider'],
                'model' => $result['model'],
            ]);
        } catch (\Throwable $throwable) {
            Log::error('AI Generate Error', [
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 500);
        }
    }
}
