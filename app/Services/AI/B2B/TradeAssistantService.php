<?php

namespace App\Services\AI\B2B;

use App\Models\B2BCompany;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TradeAssistantService
{
    public function __construct(
        protected AIManager $manager,
        protected AIRequestService $requestService,
        protected AIPromptService $promptService
    ) {
    }

    public function ask(string $question, User $user, ?int $companyId = null, ?array $context = null): array
    {
        if (!$this->manager->defaultProvider()) {
            throw new RuntimeException('No active AI provider is configured for the trade assistant.');
        }

        $company = $companyId ? $this->resolveCompany($user, $companyId) : null;

        $rendered = $this->promptService->render('b2b_trade_assistant', [
            'company_json' => json_encode($company ? [
                'company_name' => $company->company_name,
                'company_type' => $company->company_type,
                'country' => $company->country,
                'city' => $company->city,
            ] : null),
            'context_json' => json_encode($context),
            'question' => $question,
        ]);

        $result = $this->requestService->request([
            'module' => 'b2b_trade_assistant',
            'system_prompt' => $rendered['system_prompt'],
            'prompt' => $rendered['user_prompt'],
            'user' => $user,
            'company_id' => $company?->id,
        ]);

        return [
            'answer' => $result['content'],
            'provider' => $result['provider'],
            'model' => $result['model'],
        ];
    }

    protected function resolveCompany(User $user, int $companyId): B2BCompany
    {
        $allowedIds = DB::table('b2b_companies')->where('user_id', $user->id)->pluck('id')
            ->merge(
                DB::table('b2b_company_members')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->pluck('b2b_company_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        return B2BCompany::query()
            ->whereIn('id', $allowedIds)
            ->findOrFail($companyId);
    }
}
