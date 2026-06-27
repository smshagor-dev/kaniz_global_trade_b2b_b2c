<?php

namespace App\Services\AI\B2B;

use App\Models\B2BHsCode;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use Illuminate\Support\Collection;

class HSCodeAssistantService
{
    public function __construct(
        protected AIManager $manager,
        protected AIRequestService $requestService,
        protected AIPromptService $promptService
    ) {
    }

    public function suggest(array $input, ?User $user = null, ?int $companyId = null): array
    {
        $query = trim((string) ($input['query'] ?? ''));
        $country = trim((string) ($input['country'] ?? ''));
        $digits = preg_replace('/\D+/', '', $query);
        $keywords = collect(preg_split('/\s+/', mb_strtolower($query)))
            ->filter(fn ($term) => $term !== '' && mb_strlen($term) >= 3)
            ->values();

        $candidates = B2BHsCode::query()
            ->where('is_active', true)
            ->when($country !== '', fn ($builder) => $builder->where(function ($countryQuery) use ($country) {
                $countryQuery->whereNull('country')->orWhere('country', '')->orWhere('country', $country);
            }))
            ->where(function ($builder) use ($query, $digits, $keywords) {
                $builder->where('description', 'like', '%' . $query . '%');

                foreach ($keywords as $keyword) {
                    $builder->orWhere('description', 'like', '%' . $keyword . '%');
                }

                if ($digits !== '') {
                    $builder->orWhere('hs_code', 'like', '%' . $digits . '%');
                }
            })
            ->limit(10)
            ->get();

        $fallback = $this->fallbackSuggestion($candidates);

        if (!$this->manager->defaultProvider()) {
            return array_merge($fallback, [
                'source' => 'database',
                'provider' => null,
            ]);
        }

        try {
            $rendered = $this->promptService->render('b2b_hs_code_assistant', [
                'query' => $query,
                'country' => $country,
                'candidate_hs_codes_json' => json_encode($candidates->map(fn (B2BHsCode $code) => [
                    'hs_code' => $code->hs_code,
                    'description' => $code->description,
                    'country' => $code->country,
                    'restrictions' => $code->restrictions,
                    'required_documents' => $code->required_documents,
                ])->values()->all()),
            ]);

            $result = $this->requestService->request([
                'module' => 'b2b_hs_code_assistant',
                'system_prompt' => $rendered['system_prompt'],
                'prompt' => $rendered['user_prompt'],
                'user' => $user,
                'company_id' => $companyId,
            ]);

            $decoded = json_decode($result['content'], true);
            if (!is_array($decoded)) {
                throw new \RuntimeException('HS code assistant returned an invalid response.');
            }

            return array_merge($fallback, $decoded, [
                'source' => 'ai',
                'provider' => $result['provider'],
            ]);
        } catch (\Throwable $throwable) {
            return array_merge($fallback, [
                'source' => 'database',
                'provider' => null,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    protected function fallbackSuggestion(Collection $candidates): array
    {
        /** @var B2BHsCode|null $first */
        $first = $candidates->first();

        return [
            'suggested_hs_code' => $first?->hs_code,
            'description' => $first?->description,
            'confidence' => $first ? 'medium' : 'low',
            'required_documents' => $first?->required_documents ?? [],
            'restrictions' => $first?->restrictions,
            'candidates' => $candidates->map(fn (B2BHsCode $code) => [
                'hs_code' => $code->hs_code,
                'description' => $code->description,
                'country' => $code->country,
            ])->values()->all(),
        ];
    }
}
