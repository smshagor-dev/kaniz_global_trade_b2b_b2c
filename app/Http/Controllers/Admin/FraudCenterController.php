<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunAiFraudCheckJob;
use App\Jobs\RunFraudCheckJob;
use App\Models\FraudCheck;
use App\Models\FraudRule;
use App\Models\User;
use App\Models\UserDeviceLog;
use App\Models\UserReport;
use App\Models\VerificationDocument;
use App\Services\Fraud\FraudRestrictionService;
use App\Services\Fraud\FraudScoringService;
use App\Services\Fraud\FraudSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FraudCenterController extends Controller
{
    public function __construct(
        protected FraudScoringService $fraudScoringService,
        protected FraudSettingsService $settingsService,
        protected FraudRestrictionService $restrictionService
    ) {
        $this->middleware(['permission:fraud.view'])->only(['dashboard', 'users', 'show', 'documents', 'reports', 'settings']);
        $this->middleware(['permission:fraud.manage'])->only(['runCheck', 'approve', 'reject', 'restrict', 'block', 'unblock']);
        $this->middleware(['permission:fraud.review_documents'])->only(['approveDocument', 'rejectDocument', 'downloadDocument']);
        $this->middleware(['permission:fraud.run_ai_check'])->only(['runAiCheck']);
        $this->middleware(['permission:fraud.settings'])->only(['updateSettings']);
    }

    public function dashboard()
    {
        $riskTrend = collect(range(6, 0))->map(function ($days) {
            $date = now()->subDays($days)->toDateString();

            return [
                'date' => now()->subDays($days)->format('M d'),
                'count' => FraudCheck::query()->whereDate('updated_at', $date)->count(),
            ];
        });

        return view('backend.fraud.dashboard', [
            'stats' => [
                'total_flagged_users' => FraudCheck::query()->whereIn('risk_level', ['medium', 'high', 'critical', 'blocked'])->count(),
                'high_risk_suppliers' => FraudCheck::query()->where('user_type', 'supplier')->whereIn('risk_level', ['high', 'critical', 'blocked'])->count(),
                'high_risk_buyers' => FraudCheck::query()->where('user_type', 'buyer')->whereIn('risk_level', ['high', 'critical', 'blocked'])->count(),
                'pending_manual_reviews' => FraudCheck::query()->whereIn('status', ['pending', 'needs_review', 'restricted'])->count(),
                'pending_document_reviews' => VerificationDocument::query()->where('status', 'pending')->count(),
                'ai_checked_users' => FraudCheck::query()->whereNotNull('ai_score')->count(),
                'blocked_users' => FraudCheck::query()->where('status', 'blocked')->count(),
            ],
            'recentlyFlaggedUsers' => FraudCheck::query()->with('user')->whereIn('risk_level', ['medium', 'high', 'critical', 'blocked'])->latest('updated_at')->limit(8)->get(),
            'topReasons' => FraudCheck::query()->latest('updated_at')->limit(50)->get()->flatMap(function (FraudCheck $check) {
                return collect($check->reasons ?: [])->pluck('message');
            })->countBy()->sortDesc()->take(8),
            'riskTrend' => $riskTrend,
        ]);
    }

    public function users(Request $request)
    {
        $checks = FraudCheck::query()
            ->with(['user.b2bCompany'])
            ->when($request->user_type, fn ($query, $value) => $query->where('user_type', $value))
            ->when($request->risk_level, fn ($query, $value) => $query->where('risk_level', $value))
            ->when($request->status, fn ($query, $value) => $query->where('status', $value))
            ->when($request->search, function ($query, $search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('b2bCompany', fn ($companyQuery) => $companyQuery->where('company_name', 'like', '%' . $search . '%'));
                });
            })
            ->latest('updated_at')
            ->paginate(20)
            ->appends($request->query());

        return view('backend.fraud.users.index', compact('checks'));
    }

    public function show($id)
    {
        $user = User::with('b2bCompany')->findOrFail($id);
        $check = FraudCheck::query()->with(['logs', 'reviewer'])->where('user_id', $user->id)->latest('updated_at')->first();

        return view('backend.fraud.users.show', [
            'user' => $user,
            'check' => $check,
            'documents' => VerificationDocument::query()->where('user_id', $user->id)->latest()->get(),
            'reports' => UserReport::query()->with('reporter')->where('reported_user_id', $user->id)->latest()->get(),
            'deviceLogs' => UserDeviceLog::query()->where('user_id', $user->id)->latest('login_at')->limit(20)->get(),
        ]);
    }

    public function runCheck($id)
    {
        RunFraudCheckJob::dispatchSync((int) $id, [
            'event_type' => 'admin_manual_trigger',
            'reason' => 'Fraud check triggered by admin.',
            'created_by' => Auth::id(),
        ]);

        flash(translate('Fraud check executed successfully.'))->success();

        return back();
    }

    public function runAiCheck($id)
    {
        RunAiFraudCheckJob::dispatchSync((int) $id);
        flash(translate('AI fraud check executed successfully.'))->success();

        return back();
    }

    public function approve(Request $request, $id)
    {
        $this->fraudScoringService->manualReview(User::findOrFail($id), [
            'manual_score' => (int) $request->input('manual_score', 10),
            'status' => 'approved',
            'summary' => $request->input('summary', 'User approved after manual review.'),
            'reason' => $request->input('reason', 'User approved by admin.'),
            'reviewed_by' => Auth::id(),
        ]);

        flash(translate('User approved successfully.'))->success();
        return back();
    }

    public function reject(Request $request, $id)
    {
        $this->fraudScoringService->manualReview(User::findOrFail($id), [
            'manual_score' => (int) $request->input('manual_score', 70),
            'status' => 'rejected',
            'summary' => $request->input('summary', 'Verification rejected by admin.'),
            'reason' => $request->input('reason', 'Verification rejected by admin.'),
            'reviewed_by' => Auth::id(),
        ]);

        flash(translate('User verification rejected successfully.'))->success();
        return back();
    }

    public function restrict(Request $request, $id)
    {
        $this->fraudScoringService->manualReview(User::findOrFail($id), [
            'manual_score' => (int) $request->input('manual_score', 85),
            'status' => 'restricted',
            'summary' => $request->input('summary', 'User access restricted pending review.'),
            'reason' => $request->input('reason', 'User restricted by admin.'),
            'reviewed_by' => Auth::id(),
        ]);

        flash(translate('User restricted successfully.'))->success();
        return back();
    }

    public function block(Request $request, $id)
    {
        $this->fraudScoringService->manualReview(User::findOrFail($id), [
            'manual_score' => 100,
            'status' => 'blocked',
            'summary' => $request->input('summary', 'User blocked by admin.'),
            'reason' => $request->input('reason', 'User blocked by admin.'),
            'reviewed_by' => Auth::id(),
        ]);

        flash(translate('User blocked successfully.'))->success();
        return back();
    }

    public function unblock(Request $request, $id)
    {
        $this->fraudScoringService->manualReview(User::findOrFail($id), [
            'manual_score' => (int) $request->input('manual_score', 20),
            'status' => 'approved',
            'summary' => $request->input('summary', 'User restored after review.'),
            'reason' => $request->input('reason', 'User unblocked by admin.'),
            'reviewed_by' => Auth::id(),
        ]);

        flash(translate('User unblocked successfully.'))->success();
        return back();
    }

    public function documents(Request $request)
    {
        $documents = VerificationDocument::query()
            ->with('user')
            ->when($request->status, fn ($query, $value) => $query->where('status', $value))
            ->when($request->user_type, fn ($query, $value) => $query->where('user_type', $value))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('backend.fraud.documents.index', compact('documents'));
    }

    public function downloadDocument($id)
    {
        $document = VerificationDocument::findOrFail($id);

        if (Storage::disk('local')->exists($document->file_path)) {
            return Storage::disk('local')->download($document->file_path, $document->original_name);
        }

        $publicPath = public_path($document->file_path);
        abort_unless(File::exists($publicPath), 404);

        return response()->download($publicPath, $document->original_name);
    }

    public function approveDocument($id)
    {
        $document = VerificationDocument::findOrFail($id);
        $document->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        RunFraudCheckJob::dispatchSync($document->user_id, [
            'event_type' => 'document_approved',
            'reason' => 'Verification document approved.',
            'created_by' => Auth::id(),
        ]);

        flash(translate('Document approved successfully.'))->success();
        return back();
    }

    public function rejectDocument(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        $document = VerificationDocument::findOrFail($id);
        $document->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        RunFraudCheckJob::dispatchSync($document->user_id, [
            'event_type' => 'document_rejected',
            'reason' => 'Verification document rejected.',
            'created_by' => Auth::id(),
        ]);

        flash(translate('Document rejected successfully.'))->success();
        return back();
    }

    public function reports(Request $request)
    {
        $reports = UserReport::query()
            ->with(['reporter', 'reportedUser'])
            ->when($request->status, fn ($query, $value) => $query->where('status', $value))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('backend.fraud.reports.index', compact('reports'));
    }

    public function resolveReport(Request $request, $id)
    {
        $report = UserReport::findOrFail($id);
        $report->update([
            'status' => $request->input('status', 'resolved'),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        RunFraudCheckJob::dispatchSync($report->reported_user_id, [
            'event_type' => 'report_reviewed',
            'reason' => 'User report reviewed by admin.',
            'created_by' => Auth::id(),
        ]);

        flash(translate('Report updated successfully.'))->success();
        return back();
    }

    public function settings()
    {
        return view('backend.fraud.settings', [
            'settings' => $this->settingsService->all(),
            'rules' => FraudRule::query()->orderBy('name')->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'ai_enabled' => 'nullable|boolean',
            'manual_approval_suppliers' => 'nullable|boolean',
            'manual_approval_buyers' => 'nullable|boolean',
            'manual_review_threshold' => 'required|integer|min:0|max:100',
            'restriction_threshold' => 'required|integer|min:0|max:100',
            'block_threshold' => 'required|integer|min:0|max:100',
            'ai_weight_percentage' => 'required|integer|min:0|max:100',
            'rule_weight_percentage' => 'required|integer|min:0|max:100',
            'rfq_limit_per_day' => 'required|integer|min:1',
            'product_upload_limit_per_day' => 'required|integer|min:1',
            'auto_block_enabled' => 'nullable|boolean',
            'notify_admin_high_risk' => 'nullable|boolean',
            'notify_user_verification_rejected' => 'nullable|boolean',
        ]);

        $this->settingsService->update([
            ...$data,
            'enabled' => $request->boolean('enabled'),
            'ai_enabled' => $request->boolean('ai_enabled'),
            'manual_approval_suppliers' => $request->boolean('manual_approval_suppliers'),
            'manual_approval_buyers' => $request->boolean('manual_approval_buyers'),
            'auto_block_enabled' => $request->boolean('auto_block_enabled'),
            'notify_admin_high_risk' => $request->boolean('notify_admin_high_risk'),
            'notify_user_verification_rejected' => $request->boolean('notify_user_verification_rejected'),
        ]);

        flash(translate('Fraud settings updated successfully.'))->success();
        return back();
    }
}
