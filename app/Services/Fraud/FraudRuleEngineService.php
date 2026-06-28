<?php

namespace App\Services\Fraud;

use App\Models\B2BCompany;
use App\Models\B2BRfq;
use App\Models\FraudRule;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserDeviceLog;
use App\Models\UserReport;
use App\Models\VerificationDocument;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FraudRuleEngineService
{
    public function evaluateUser(User $user, ?B2BCompany $company = null): array
    {
        $company ??= $user->b2bCompany;
        $userType = $company?->isSupplierSide() || $user->user_type === 'seller' ? 'supplier' : 'buyer';
        $reasons = [];
        $score = 0;

        $add = function (string $code, string $message, int $defaultScore, string $severity = 'medium') use (&$reasons, &$score, $userType): void {
            $rule = FraudRule::query()->where('code', $code)->where('is_active', true)->first();
            $ruleScore = $rule ? (int) $rule->score : $defaultScore;
            if ($ruleScore <= 0) {
                return;
            }

            $score += $ruleScore;
            $reasons[] = [
                'code' => $code,
                'message' => $message,
                'score' => $ruleScore,
                'severity' => $rule?->severity ?? $severity,
                'user_type' => $userType,
            ];
        };

        if (!$user->email_verified_at) {
            $add('unverified_email', 'Email is not verified.', 10, 'low');
        }

        if ((int) ($user->verification_status ?? 0) !== 1) {
            $add('unverified_phone', 'Phone verification is incomplete.', 15, 'medium');
        }

        $duplicateEmailCount = User::query()->where('email', $user->email)->count();
        if ($user->email && $duplicateEmailCount > 1) {
            $add('duplicate_email', 'Email is shared by multiple accounts.', 20, 'high');
        }

        $duplicatePhoneCount = $user->phone ? User::query()->where('phone', $user->phone)->count() : 0;
        if ($duplicatePhoneCount > 1) {
            $add('duplicate_phone', 'Phone number is shared by multiple accounts.', 25, 'high');
        }

        $latestIp = UserDeviceLog::query()->where('user_id', $user->id)->latest('login_at')->value('ip_address');
        if ($latestIp) {
            $duplicateIpUsers = UserDeviceLog::query()->where('ip_address', $latestIp)->distinct('user_id')->count('user_id');
            if ($duplicateIpUsers >= 5) {
                $add('duplicate_ip_many_accounts', 'Same IP address is used by many accounts.', 25, 'high');
            }
        }

        $latestDeviceHash = UserDeviceLog::query()->where('user_id', $user->id)->latest('login_at')->value('device_hash');
        if ($latestDeviceHash) {
            $duplicateDeviceUsers = UserDeviceLog::query()->where('device_hash', $latestDeviceHash)->distinct('user_id')->count('user_id');
            if ($duplicateDeviceUsers >= 3) {
                $add('duplicate_device_many_accounts', 'Same device is used by multiple accounts.', 30, 'critical');
            }
        }

        $reportsCount = UserReport::query()
            ->where('reported_user_id', $user->id)
            ->whereIn('status', ['pending', 'investigating', 'resolved'])
            ->count();

        if ($reportsCount >= 5) {
            $add('reported_by_users_high', 'User received many marketplace reports.', 60, 'critical');
        } elseif ($reportsCount >= 3) {
            $add('reported_by_users_medium', 'User received repeated marketplace reports.', 35, 'high');
        }

        if ($company) {
            $this->evaluateCompanySignals($user, $company, $add);
        } else {
            $this->evaluateBuyerSignals($user, $add);
        }

        $score = min(100, $score);

        return [
            'user_type' => $userType,
            'score' => $score,
            'risk_level' => $this->levelFromScore($score),
            'reasons' => $reasons,
        ];
    }

    protected function evaluateCompanySignals(User $user, B2BCompany $company, callable $add): void
    {
        if ($company->isSupplierSide()) {
            if (!$company->trade_license_file || !$company->tax_document_file) {
                $add('supplier_missing_business_document', 'Required business documents are missing.', 20, 'high');
            }

            $rejectedDocs = VerificationDocument::query()
                ->where('user_id', $user->id)
                ->where('status', 'rejected')
                ->count();
            if ($rejectedDocs > 0) {
                $add('supplier_document_rejected', 'A verification document was rejected.', 40, 'critical');
            }

            if ($company->bank_account_name && !$this->containsNormalized($company->bank_account_name, $company->company_name)) {
                $add('bank_company_name_mismatch', 'Bank account name does not match company name.', 40, 'critical');
            }

            if ($company->legal_name && !$this->containsNormalized($company->legal_name, $company->company_name)) {
                $add('company_name_mismatch', 'Legal name does not align with company name.', 35, 'high');
            }

            $suspiciousKeywords = ['test', 'fake', 'demo', 'sample only'];
            if (Str::contains(Str::lower($company->company_name), $suspiciousKeywords)) {
                $add('fake_company_name', 'Company name appears suspicious.', 15, 'medium');
            }

            $missingFields = collect([
                $company->country,
                $company->city,
                $company->address,
                $company->website,
                $company->description,
            ])->filter(fn ($value) => blank($value))->count();
            if ($missingFields >= 3) {
                $add('incomplete_company_profile', 'Company profile is incomplete.', 15, 'medium');
            }

            $recentProducts = Product::query()
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            if ($recentProducts >= 20) {
                $add('many_product_uploads_short_time', 'Too many product uploads in a short time.', 25, 'high');
            }

            $rejectedProducts = Product::query()
                ->where('user_id', $user->id)
                ->where('approved', 0)
                ->count();
            if ($rejectedProducts >= 5) {
                $add('many_rejected_products', 'Multiple products were rejected.', 20, 'medium');
            }

            $duplicateThumbnailProducts = Product::query()
                ->where('user_id', $user->id)
                ->whereNotNull('thumbnail_img')
                ->selectRaw('thumbnail_img, COUNT(*) as total')
                ->groupBy('thumbnail_img')
                ->havingRaw('COUNT(*) > 1')
                ->count();
            if ($duplicateThumbnailProducts > 0) {
                $add('duplicate_product_image', 'Duplicate product images detected.', 20, 'medium');
            }

            $duplicateDescriptions = Product::query()
                ->where('user_id', $user->id)
                ->whereNotNull('description')
                ->selectRaw('description, COUNT(*) as total')
                ->groupBy('description')
                ->havingRaw('COUNT(*) > 1')
                ->count();
            if ($duplicateDescriptions > 0) {
                $add('copied_product_description', 'Repeated product descriptions detected.', 15, 'medium');
            }

            $categoryProduct = Product::query()
                ->where('user_id', $user->id)
                ->whereNotNull('category_id')
                ->with('stocks')
                ->latest('id')
                ->first();
            if ($categoryProduct) {
                $categoryAverage = Product::query()
                    ->where('category_id', $categoryProduct->category_id)
                    ->where('approved', 1)
                    ->join('product_stocks', 'products.id', '=', 'product_stocks.product_id')
                    ->avg('product_stocks.price');

                $ownAverage = $categoryProduct->stocks->avg('price');
                if ($categoryAverage && $ownAverage && $ownAverage < ($categoryAverage * 0.30)) {
                    $add('very_low_product_price', 'Products are priced far below category average.', 25, 'high');
                }
            }
        } else {
            $this->evaluateBuyerSignals($user, $add);
        }

        if ($user->created_at && $user->created_at->gt(now()->subDays(7))) {
            $activityCount = B2BRfq::query()->where('user_id', $user->id)->where('created_at', '>=', now()->subDays(7))->count()
                + Product::query()->where('user_id', $user->id)->where('created_at', '>=', now()->subDays(7))->count();
            if ($activityCount >= 15) {
                $add('new_account_high_activity', 'Recently created account has unusually high activity.', 20, 'high');
            }
        }
    }

    protected function evaluateBuyerSignals(User $user, callable $add): void
    {
        $rfqsLast24Hours = B2BRfq::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($rfqsLast24Hours >= 50) {
            $add('rfq_volume_extreme', 'Buyer created too many RFQs within 24 hours.', 50, 'critical');
        } elseif ($rfqsLast24Hours >= 20) {
            $add('rfq_volume_high', 'Buyer created a high number of RFQs within 24 hours.', 30, 'high');
        }

        $cancelledOrders = Order::query()
            ->where('user_id', $user->id)
            ->whereIn('delivery_status', ['cancelled', 'returned'])
            ->count();
        if ($cancelledOrders >= 5) {
            $add('too_many_cancelled_orders', 'Buyer has many cancelled orders.', 20, 'medium');
        }

        if (
            Schema::hasTable('b2b_payment_transactions')
            && Schema::hasColumn('b2b_payment_transactions', 'user_id')
            && Schema::hasColumn('b2b_payment_transactions', 'status')
        ) {
            $paymentFailures = \DB::table('b2b_payment_transactions')
                ->where('user_id', $user->id)
                ->whereIn('status', ['failed', 'declined'])
                ->count();

            if ($paymentFailures >= 5) {
                $add('many_payment_failures', 'Repeated payment failures detected.', 30, 'high');
            }
        }

        $missingFields = collect([$user->address, $user->city, $user->country, $user->phone])->filter(fn ($value) => blank($value))->count();
        if ($missingFields >= 2) {
            $add('incomplete_buyer_profile', 'Buyer profile is incomplete.', 10, 'low');
        }

        if ($user->created_at && $user->created_at->gt(now()->subDays(7)) && $rfqsLast24Hours >= 10) {
            $add('new_buyer_high_rfq_volume', 'New buyer account created high RFQ volume quickly.', 20, 'high');
        }
    }

    protected function containsNormalized(string $left, string $right): bool
    {
        $normalize = fn (string $value) => preg_replace('/[^a-z0-9]+/', '', Str::lower($value));

        return Str::contains($normalize($left), $normalize($right))
            || Str::contains($normalize($right), $normalize($left));
    }

    public function levelFromScore(int $score): string
    {
        return match (true) {
            $score <= 20 => 'safe',
            $score <= 40 => 'low',
            $score <= 60 => 'medium',
            $score <= 80 => 'high',
            $score <= 99 => 'critical',
            default => 'blocked',
        };
    }
}
