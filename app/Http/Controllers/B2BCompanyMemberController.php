<?php

namespace App\Http\Controllers;

use App\Models\B2BCompanyInvitation;
use App\Models\B2BCompany;
use App\Models\B2BCompanyMember;
use App\Models\NotificationType;
use App\Models\User;
use App\Notifications\CustomNotification;
use App\Services\B2BAuditService;
use App\Services\B2BCompanyService;
use App\Services\B2BPackageService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class B2BCompanyMemberController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService,
        protected B2BAuditService $b2bAuditService,
        protected B2BPackageService $b2bPackageService
    ) {
    }

    public function index()
    {
        $company = $this->getAccessibleCompany();

        $members = $company->members()->with(['user', 'inviter'])->orderByRaw("FIELD(role, 'owner', 'admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer')")->get();
        $invitations = $company->invitations()->with('inviter')->latest()->get();
        $canInvite = $this->b2bPermissionService->canInviteMembers(Auth::id(), $company->id)
            && $this->b2bCompanyService->hasActivePackage(Auth::id(), $company->id)
            && $this->b2bPackageService->canInviteMoreMembers($company);

        return view('b2b.company.members.index', compact('company', 'members', 'invitations', 'canInvite'));
    }

    public function invite()
    {
        $company = $this->getAccessibleCompany();
        $this->ensureCanInvite($company->id);

        return view('b2b.company.members.invite', compact('company'));
    }

    public function sendInvite(Request $request)
    {
        $company = $this->getAccessibleCompany();
        $this->ensureCanInvite($company->id);

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'role' => ['required', Rule::in(['admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer'])],
        ]);

        $existingMember = User::where('email', $data['email'])->first();
        if ($existingMember && B2BCompanyMember::where('b2b_company_id', $company->id)->where('user_id', $existingMember->id)->whereIn('status', ['invited', 'active', 'suspended'])->exists()) {
            flash(translate('This user is already linked with your company team.'))->warning();
            return back();
        }

        $invitation = B2BCompanyInvitation::updateOrCreate(
            [
                'b2b_company_id' => $company->id,
                'email' => $data['email'],
                'status' => 'pending',
            ],
            [
                'role' => $data['role'],
                'token' => Str::random(64),
                'invited_by' => Auth::id(),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ]
        );

        if ($existingMember) {
            B2BCompanyMember::updateOrCreate(
                [
                    'b2b_company_id' => $company->id,
                    'user_id' => $existingMember->id,
                ],
                [
                    'role' => $data['role'],
                    'status' => 'invited',
                    'invited_by' => Auth::id(),
                    'joined_at' => null,
                ]
            );
        }

        $inviteUrl = route('b2b.company.invitations.accept', $invitation->token);
        $mailSent = $this->sendInvitationEmail($company->company_name, $data['email'], $data['role'], $inviteUrl);
        $this->sendInvitationNotification($existingMember, $inviteUrl);

        $this->b2bAuditService->log(Auth::id(), $company->id, 'member_invited', $company, 'Company member invited.', [
            'email' => $data['email'],
            'role' => $data['role'],
            'invitation_id' => $invitation->id,
        ]);

        flash($mailSent
            ? translate('Invitation sent successfully.')
            : translate('Invitation created successfully. Mail could not be sent, so use the invitation link from the team page for testing.')
        )->success();

        return redirect()->route('b2b.company.members.index');
    }

    public function acceptInvite($token)
    {
        $invitation = B2BCompanyInvitation::with(['company', 'inviter'])->where('token', $token)->firstOrFail();

        if (!Auth::check()) {
            flash(translate('Please login with the invited email address to accept this invitation.'))->warning();
            return redirect()->route('user.login');
        }

        $status = 'accepted';
        $message = translate('Invitation accepted successfully.');

        if ($invitation->status !== 'pending') {
            $status = 'invalid';
            $message = translate('This invitation is no longer pending.');
        } elseif ($invitation->expires_at->isPast()) {
            $invitation->update(['status' => 'expired']);
            $status = 'expired';
            $message = translate('This invitation has expired.');
        } elseif (strcasecmp(Auth::user()->email, $invitation->email) !== 0) {
            $status = 'mismatch';
            $message = translate('You must login with the invited email address to accept this invitation.');
        } elseif (
            !$this->b2bCompanyService->hasActivePackage(Auth::id(), $invitation->b2b_company_id) ||
            !$this->b2bPackageService->canInviteMoreMembers($invitation->company)
        ) {
            $status = 'limit_reached';
            $message = translate('This invitation cannot be accepted because the company package is inactive or the member limit has been reached.');
        } else {
            $member = B2BCompanyMember::updateOrCreate(
                [
                    'b2b_company_id' => $invitation->b2b_company_id,
                    'user_id' => Auth::id(),
                ],
                [
                    'role' => $invitation->role,
                    'status' => 'active',
                    'invited_by' => $invitation->invited_by,
                    'joined_at' => now(),
                ]
            );

            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $this->b2bAuditService->log(Auth::id(), $invitation->b2b_company_id, 'invitation_accepted', $member, 'Company invitation accepted.', [
                'invitation_id' => $invitation->id,
                'role' => $member->role,
            ]);
        }

        return view('b2b.company.invitations.accept', compact('invitation', 'status', 'message'));
    }

    public function updateRole(Request $request, $id)
    {
        $company = $this->getAccessibleCompany();
        $this->ensureCanInvite($company->id);

        $member = B2BCompanyMember::where('b2b_company_id', $company->id)->findOrFail($id);
        if ($member->role === 'owner') {
            flash(translate('The company owner role cannot be changed.'))->warning();
            return back();
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer'])],
        ]);

        $oldRole = $member->role;
        $member->update(['role' => $data['role']]);

        B2BCompanyInvitation::where('b2b_company_id', $company->id)
            ->where('email', $member->user?->email)
            ->where('status', 'pending')
            ->update(['role' => $data['role']]);

        $this->b2bAuditService->log(Auth::id(), $company->id, 'member_role_changed', $member, 'Company member role updated.', [
            'old_role' => $oldRole,
            'new_role' => $data['role'],
            'member_user_id' => $member->user_id,
        ]);

        flash(translate('Member role updated successfully.'))->success();
        return back();
    }

    public function suspend($id)
    {
        $company = $this->getAccessibleCompany();
        $this->ensureCanInvite($company->id);

        $member = B2BCompanyMember::where('b2b_company_id', $company->id)->findOrFail($id);
        if ($member->role === 'owner') {
            flash(translate('The company owner cannot be suspended.'))->warning();
            return back();
        }

        $member->update(['status' => 'suspended']);

        $this->b2bAuditService->log(Auth::id(), $company->id, 'member_suspended', $member, 'Company member suspended.', [
            'member_user_id' => $member->user_id,
        ]);

        flash(translate('Member suspended successfully.'))->success();
        return back();
    }

    public function remove($id)
    {
        $company = $this->getAccessibleCompany();
        $this->ensureCanInvite($company->id);

        $member = B2BCompanyMember::where('b2b_company_id', $company->id)->findOrFail($id);
        if ($member->role === 'owner') {
            flash(translate('The company owner cannot be removed.'))->warning();
            return back();
        }

        $member->update(['status' => 'removed']);

        B2BCompanyInvitation::where('b2b_company_id', $company->id)
            ->where('email', $member->user?->email)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $this->b2bAuditService->log(Auth::id(), $company->id, 'member_removed', $member, 'Company member removed.', [
            'member_user_id' => $member->user_id,
        ]);

        flash(translate('Member removed successfully.'))->success();
        return back();
    }

    protected function getAccessibleCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company && $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id),
            403
        );

        return $company;
    }

    protected function ensureCanInvite(int $companyId): void
    {
        abort_unless($this->b2bPermissionService->canInviteMembers(Auth::id(), $companyId), 403);

        $company = B2BCompany::find($companyId);

        abort_unless(
            $company &&
            $this->b2bCompanyService->hasActivePackage(Auth::id(), $companyId) &&
            $this->b2bPackageService->canInviteMoreMembers($company),
            403
        );
    }

    protected function sendInvitationEmail(string $companyName, string $email, string $role, string $inviteUrl): bool
    {
        try {
            if (!config('mail.default')) {
                return false;
            }

            Mail::raw(
                "You have been invited to join {$companyName} as {$role}. Accept the invitation here: {$inviteUrl}",
                function ($message) use ($email, $companyName) {
                    $message->to($email)->subject("B2B Company Invitation - {$companyName}");
                }
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function sendInvitationNotification(?User $user, string $inviteUrl): void
    {
        if (!$user || !Schema::hasTable('notifications') || !Schema::hasTable('notification_types')) {
            return;
        }

        $notificationType = NotificationType::query()
            ->where('type', 'custom')
            ->where('status', 1)
            ->first();

        if (!$notificationType) {
            return;
        }

        Notification::send(collect([$user]), new CustomNotification([
            'link' => $inviteUrl,
            'notification_type_id' => $notificationType->id,
        ]));
    }
}
