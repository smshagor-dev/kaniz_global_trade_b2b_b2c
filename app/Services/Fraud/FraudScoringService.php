<?php

namespace App\Services\Fraud;

use App\Models\B2BCompany;
use App\Models\FraudCheck;
use App\Models\FraudCheckLog;
use App\Models\User;
use App\Models\UserRiskEvent;
use App\Models\VerificationDocument;
use Illuminate\Support\Facades\DB;

class FraudScoringService
{
    public function __construct(
        protected FraudRuleEngineService $ruleEngine,
        protected AiFraudAnalysisService $aiFraudAnalysisService,
        protected FraudSettingsService $settingsService
    ) {
    }

    public function runForUser(User $user, array $options = []): FraudCheck
    {
        $company = $user->b2bCompany;
        $existing = FraudCheck::query()->where('user_id', $user->id)->latest('updated_at')->first();
        $manualOverride = $existing && $existing->source === 'manual' && !($options['ignore_manual_override'] ?? false);

        $ruleResult = $this->ruleEngine->evaluateUser($user, $company);
        $aiResult = null;

        if (($options['run_ai'] ?? true) && !$manualOverride) {
            $aiResult = $this->aiFraudAnalysisService->analyze(
                $user,
                $this->profileSummary($user, $company),
                $ruleResult['score'],
                $ruleResult['reasons']
            );
        }

        $finalScore = $this->combineScores($ruleResult['score'], $aiResult['risk_score'] ?? null);
        $finalScore = $this->applyMinimums($user, $finalScore);
        $riskLevel = $this->ruleEngine->levelFromScore($finalScore);
        $status = $this->statusFromScore($finalScore);

        if ($manualOverride) {
            $finalScore = (int) ($existing->manual_score ?? $existing->final_score ?? $finalScore);
            $riskLevel = $existing->risk_level;
            $status = $existing->status;
        }

        if (($options['force_block'] ?? false) === true) {
            $finalScore = 100;
            $riskLevel = 'blocked';
            $status = 'blocked';
        }

        return DB::transaction(function () use ($user, $existing, $ruleResult, $aiResult, $finalScore, $riskLevel, $status, $options): FraudCheck {
            $check = $existing ?: new FraudCheck(['user_id' => $user->id]);
            $oldScore = $check->final_score;

            $check->fill([
                'user_type' => $ruleResult['user_type'],
                'risk_score' => $finalScore,
                'risk_level' => $riskLevel,
                'source' => $aiResult ? 'combined' : 'rule_based',
                'status' => $status,
                'summary' => $aiResult['summary'] ?? $this->defaultSummary($riskLevel),
                'reasons' => array_merge($ruleResult['reasons'], $aiResult['reasons'] ?? []),
                'rule_score' => $ruleResult['score'],
                'ai_score' => $aiResult['risk_score'] ?? null,
                'manual_score' => $check->manual_score,
                'final_score' => $finalScore,
                'ai_provider' => $aiResult['provider'] ?? null,
                'ai_model' => $aiResult['model'] ?? null,
                'ai_response' => $aiResult['raw'] ?? null,
                'reviewed_by' => $options['reviewed_by'] ?? $check->reviewed_by,
                'reviewed_at' => $options['reviewed_at'] ?? $check->reviewed_at,
            ]);
            $check->save();

            FraudCheckLog::query()->create([
                'fraud_check_id' => $check->id,
                'user_id' => $user->id,
                'event_type' => $options['event_type'] ?? 'fraud_check_ran',
                'old_score' => $oldScore,
                'new_score' => $finalScore,
                'reason' => $options['reason'] ?? 'Fraud scoring recalculated.',
                'metadata' => [
                    'rule_score' => $ruleResult['score'],
                    'ai_score' => $aiResult['risk_score'] ?? null,
                    'risk_level' => $riskLevel,
                ],
                'created_by' => $options['created_by'] ?? null,
                'created_at' => now(),
            ]);

            UserRiskEvent::query()->create([
                'user_id' => $user->id,
                'user_type' => $ruleResult['user_type'],
                'event_type' => $options['event_type'] ?? 'fraud_check_ran',
                'score' => $finalScore,
                'reason' => $options['reason'] ?? 'Fraud scoring recalculated.',
                'metadata' => ['fraud_check_id' => $check->id],
                'created_at' => now(),
            ]);

            if ($status === 'blocked') {
                $user->forceFill(['banned' => 1])->save();
            } elseif (($options['unblock_user'] ?? false) === true) {
                $user->forceFill(['banned' => 0])->save();
            }

            return $check;
        });
    }

    public function manualReview(User $user, array $data): FraudCheck
    {
        return DB::transaction(function () use ($user, $data) {
            $check = FraudCheck::query()->where('user_id', $user->id)->latest('updated_at')->firstOrFail();
            $oldScore = $check->final_score;

            $score = max(0, min(100, (int) ($data['manual_score'] ?? $oldScore ?? 0)));
            $status = (string) ($data['status'] ?? $this->statusFromScore($score));
            $riskLevel = $this->ruleEngine->levelFromScore($score);

            $check->update([
                'source' => 'manual',
                'manual_score' => $score,
                'final_score' => $score,
                'risk_score' => $score,
                'risk_level' => $status === 'blocked' ? 'blocked' : $riskLevel,
                'status' => $status,
                'summary' => $data['summary'] ?? $check->summary,
                'reviewed_by' => $data['reviewed_by'],
                'reviewed_at' => now(),
            ]);

            FraudCheckLog::query()->create([
                'fraud_check_id' => $check->id,
                'user_id' => $user->id,
                'event_type' => 'manual_review',
                'old_score' => $oldScore,
                'new_score' => $score,
                'reason' => $data['reason'] ?? 'Manual fraud review completed.',
                'metadata' => ['status' => $status],
                'created_by' => $data['reviewed_by'],
                'created_at' => now(),
            ]);

            $user->forceFill(['banned' => $status === 'blocked' ? 1 : 0])->save();

            return $check->fresh();
        });
    }

    protected function combineScores(int $ruleScore, ?int $aiScore): int
    {
        if ($aiScore === null || !$this->settingsService->get('ai_enabled', false)) {
            return $ruleScore;
        }

        $ruleWeight = max(0, min(100, (int) $this->settingsService->get('rule_weight_percentage', 65))) / 100;
        $aiWeight = max(0, min(100, (int) $this->settingsService->get('ai_weight_percentage', 35))) / 100;

        return (int) min(100, round(($ruleScore * $ruleWeight) + ($aiScore * $aiWeight)));
    }

    protected function applyMinimums(User $user, int $score): int
    {
        $hasRejectedFraudDoc = VerificationDocument::query()
            ->where('user_id', $user->id)
            ->where('status', 'rejected')
            ->where(function ($query) {
                $query->whereNotNull('rejection_reason')->where('rejection_reason', 'like', '%fraud%')
                    ->orWhere('rejection_reason', 'like', '%fake%');
            })
            ->exists();

        if ($hasRejectedFraudDoc) {
            $score = max($score, 75);
        }

        return min(100, $score);
    }

    protected function statusFromScore(int $score): string
    {
        if ($score <= 40) {
            return 'approved';
        }

        if ($score <= 60) {
            return 'needs_review';
        }

        if ($score <= 80) {
            return 'pending';
        }

        return $this->settingsService->get('auto_block_enabled', false) ? 'blocked' : 'restricted';
    }

    protected function profileSummary(User $user, ?B2BCompany $company): array
    {
        return [
            'company_id' => $company?->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'user_type' => $user->user_type,
                'email_verified' => (bool) $user->email_verified_at,
                'verification_status' => $user->verification_status,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
            'company' => $company ? [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_type' => $company->company_type,
                'country' => $company->country,
                'city' => $company->city,
                'verification_status' => $company->verification_status,
                'business_email' => $company->business_email,
            ] : null,
            'documents' => VerificationDocument::query()
                ->where('user_id', $user->id)
                ->get(['document_type', 'status', 'rejection_reason', 'expires_at'])
                ->toArray(),
        ];
    }

    protected function defaultSummary(string $riskLevel): string
    {
        return match ($riskLevel) {
            'safe', 'low' => 'User appears low risk based on current marketplace signals.',
            'medium' => 'User has moderate fraud indicators and should be monitored.',
            'high', 'critical' => 'User shows elevated fraud indicators and needs review.',
            default => 'User has been blocked due to high fraud risk.',
        };
    }
}
