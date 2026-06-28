<?php

namespace App\Http\Controllers;

use App\Jobs\RunFraudCheckJob;
use App\Models\User;
use App\Models\UserReport;
use App\Models\VerificationDocument;
use App\Services\Fraud\FraudRestrictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FraudUserVerificationController extends Controller
{
    public function __construct(
        protected FraudRestrictionService $restrictionService
    ) {
    }

    public function index()
    {
        $user = Auth::user();
        $documents = VerificationDocument::query()->where('user_id', $user->id)->latest()->get();
        $check = $this->restrictionService->latestCheck($user);

        return view('b2b.verification.index', [
            'documents' => $documents,
            'check' => $check,
            'trustStatus' => $this->restrictionService->userFacingStatus($check),
            'company' => $user->b2bCompany,
        ]);
    }

    public function uploadDocuments(Request $request)
    {
        $data = $request->validate([
            'document_type' => 'required|in:business_license,tax_certificate,national_id,passport,bank_statement,company_registration,address_proof,other',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $file = $request->file('document');
        $filePath = $file->storeAs(
            'private/fraud-documents/' . Auth::id(),
            time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension(),
            'local'
        );

        VerificationDocument::query()->create([
            'user_id' => Auth::id(),
            'user_type' => Auth::user()->user_type === 'seller' ? 'supplier' : 'buyer',
            'document_type' => $data['document_type'],
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'status' => 'pending',
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        RunFraudCheckJob::dispatch(Auth::id(), [
            'event_type' => 'document_uploaded',
            'reason' => 'User uploaded verification document.',
        ]);

        flash(translate('Document uploaded successfully.'))->success();
        return back();
    }

    public function trustStatus()
    {
        $check = $this->restrictionService->latestCheck(Auth::id());

        return response()->json([
            'status' => $this->restrictionService->userFacingStatus($check),
            'documents_pending' => VerificationDocument::query()->where('user_id', Auth::id())->where('status', 'pending')->count(),
        ]);
    }

    public function reportUser(Request $request, $id)
    {
        $reportedUser = User::findOrFail($id);
        abort_if($reportedUser->id === Auth::id(), 422);

        $data = $request->validate([
            'report_type' => 'required|in:scam,fake_supplier,fake_buyer,payment_fraud,fake_document,spam,abuse,other',
            'message' => 'nullable|string|max:2000',
            'evidence' => 'nullable|array',
            'evidence.*' => 'nullable|string|max:500',
        ]);

        UserReport::query()->create([
            'reporter_id' => Auth::id(),
            'reported_user_id' => $reportedUser->id,
            'reported_user_type' => $reportedUser->user_type === 'seller' ? 'supplier' : 'buyer',
            'report_type' => $data['report_type'],
            'message' => $data['message'] ?? null,
            'evidence' => $data['evidence'] ?? [],
            'status' => 'pending',
        ]);

        RunFraudCheckJob::dispatch($reportedUser->id, [
            'event_type' => 'user_report_submitted',
            'reason' => 'Marketplace user submitted a report.',
        ]);

        flash(translate('Report submitted successfully.'))->success();
        return back();
    }
}
