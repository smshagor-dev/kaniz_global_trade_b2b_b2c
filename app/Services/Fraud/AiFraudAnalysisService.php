<?php

namespace App\Services\Fraud;

use App\Models\User;
use App\Services\AI\AIRequestService;
use Illuminate\Support\Facades\Log;

class AiFraudAnalysisService
{
    public function __construct(
        protected AIRequestService $aiRequestService,
        protected FraudSettingsService $settingsService
    ) {
    }

    public function analyze(User $user, array $profileSummary, int $ruleScore, array $reasons): ?array
    {
        if (!$this->settingsService->get('enabled', true) || !$this->settingsService->get('ai_enabled', false)) {
            return null;
        }

        $prompt = $this->buildPrompt($profileSummary, $ruleScore, $reasons);

        try {
            $response = $this->aiRequestService->request([
                'user' => $user,
                'company_id' => $profileSummary['company_id'] ?? null,
                'module' => 'b2b_fraud_detection',
                'system_prompt' => 'You are a fraud risk analyst for a B2B marketplace. Return JSON only.',
                'prompt' => $prompt,
                'temperature' => 0.2,
                'max_tokens' => 900,
                'metadata' => ['fraud_analysis' => true],
            ]);

            $payload = $this->parseJson($response['content'] ?? '');
            if (!$payload || !isset($payload['risk_score'], $payload['risk_level'])) {
                throw new \RuntimeException('Invalid AI fraud JSON payload.');
            }

            return [
                'risk_score' => max(0, min(100, (int) $payload['risk_score'])),
                'risk_level' => (string) $payload['risk_level'],
                'summary' => (string) ($payload['summary'] ?? ''),
                'reasons' => (array) ($payload['reasons'] ?? []),
                'recommended_action' => (string) ($payload['recommended_action'] ?? 'manual_review'),
                'checks' => (array) ($payload['checks'] ?? []),
                'provider' => $response['provider'] ?? null,
                'model' => $response['model'] ?? null,
                'raw' => $payload,
            ];
        } catch (\Throwable $throwable) {
            Log::warning('AI fraud analysis failed', [
                'user_id' => $user->id,
                'message' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    protected function buildPrompt(array $profileSummary, int $ruleScore, array $reasons): string
    {
        return <<<PROMPT
You are a fraud risk analyst for a B2B marketplace. Analyze the user and return a fraud risk result.

Return JSON only with this exact structure:
{
  "risk_score": 0,
  "risk_level": "safe|low|medium|high|critical|blocked",
  "confidence": 0,
  "summary": "",
  "reasons": [],
  "recommended_action": "approve|manual_review|restrict|block",
  "checks": {
    "identity_risk": 0,
    "document_risk": 0,
    "behavior_risk": 0,
    "payment_risk": 0,
    "duplicate_account_risk": 0,
    "communication_risk": 0
  }
}

Rules:
- Score must be 0 to 100.
- Do not invent facts.
- Base your answer only on the provided data.
- If data is missing, mark uncertainty in summary.
- If user seems suspicious but evidence is incomplete, recommend manual_review.
- If strong evidence of duplicate/fake/payment scam exists, recommend block or restrict.

Input JSON:
{$this->safeJson([
    'profile_summary' => $profileSummary,
    'rule_score' => $ruleScore,
    'rule_reasons' => $reasons,
])}
PROMPT;
    }

    protected function parseJson(string $content): ?array
    {
        $trimmed = trim($content);
        $trimmed = preg_replace('/^```json|```$/m', '', $trimmed);
        $decoded = json_decode(trim((string) $trimmed), true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function safeJson(array $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
