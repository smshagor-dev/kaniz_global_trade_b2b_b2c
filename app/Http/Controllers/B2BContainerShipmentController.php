<?php

namespace App\Http\Controllers;

use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;
use App\Services\B2BCompanyService;
use App\Services\B2BFreightService;
use App\Services\B2BIntegrationManagementService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class B2BContainerShipmentController extends Controller
{
    public function __construct(
        protected B2BCompanyService $companyService,
        protected B2BPermissionService $permissionService,
        protected B2BFreightService $freightService,
        protected B2BIntegrationManagementService $integrationService
    ) {
    }

    public function createFromQuote(Request $request, $quoteId)
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless(
            $company &&
            $this->permissionService->canAccessCompany(Auth::id(), $company->id) &&
            $this->permissionService->canManageFreight(Auth::id(), $company->id),
            403
        );

        $quote = B2BFreightQuote::with(['forwarder', 'originPort', 'destinationPort'])
            ->where(function ($query) use ($company) {
                $query->where('buyer_company_id', $company->id)
                    ->orWhere('supplier_company_id', $company->id);
            })
            ->findOrFail($quoteId);

        if ($quote->freight_mode !== 'sea_freight') {
            $result = [
                'success' => false,
                'message' => translate('Container booking is available only for sea freight quotes.'),
            ];

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json($result, 422);
            }

            flash($result['message'])->warning();

            return back();
        }

        if ($quote->status !== 'selected') {
            $result = [
                'success' => false,
                'message' => translate('Please select the freight quote before creating a container booking.'),
            ];

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json($result, 422);
            }

            flash($result['message'])->warning();

            return back();
        }

        $result = $this->freightService->createContainerShipment($quote, $request->all());

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        }

        flash(translate($result['message'] ?? 'Container booking request completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();

        return back();
    }

    public function track(Request $request)
    {
        $request->validate([
            'container_number' => 'nullable|string',
            'bill_of_lading_number' => 'nullable|string',
            'booking_number' => 'nullable|string',
        ]);

        $shipment = B2BContainerShipment::query()
            ->when($request->container_number, fn ($query, $value) => $query->where('container_number', $value))
            ->when(!$request->container_number && $request->bill_of_lading_number, fn ($query, $value) => $query->where('bill_of_lading_number', $value))
            ->when(!$request->container_number && !$request->bill_of_lading_number && $request->booking_number, fn ($query, $value) => $query->where('booking_number', $value))
            ->with(['events.port', 'forwarder', 'freightQuote'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'shipment' => $shipment,
            'timeline' => $this->freightService->buildContainerTimeline($shipment),
        ]);
    }

    public function sync($id)
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless(
            $company &&
            $this->permissionService->canAccessCompany(Auth::id(), $company->id) &&
            $this->permissionService->canManageFreight(Auth::id(), $company->id),
            403
        );

        $shipment = B2BContainerShipment::with(['forwarder', 'freightQuote'])->findOrFail($id);
        abort_unless(
            (int) ($shipment->freightQuote?->buyer_company_id ?? 0) === (int) $company->id
            || (int) ($shipment->freightQuote?->supplier_company_id ?? 0) === (int) $company->id,
            403
        );
        $result = $this->freightService->syncContainerShipment($shipment);
        if ($shipment->forwarder) {
            $this->integrationService->touchLastSync($shipment->forwarder);
        }

        if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        }

        flash(translate($result['message'] ?? 'Container shipment sync completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();

        return back();
    }

    public function handleWebhook(Request $request, $forwarder, ?string $channel = null)
    {
        $signature = $request->header('X-Webhook-Signature')
            ?: $request->header('X-Signature')
            ?: $request->input('signature');

        $forwarderModel = B2BFreightForwarder::find($forwarder)
            ?? B2BFreightForwarder::where('driver', $forwarder)->orWhere('name', $forwarder)->firstOrFail();

        if (filled($forwarderModel->webhook_secret) && !hash_equals((string) $forwarderModel->webhook_secret, (string) $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        $result = $this->freightService->handleWebhook($forwarderModel, $request->all(), $signature);
        $this->integrationService->recordWebhookReceived($forwarderModel, (bool) ($result['success'] ?? false));
        if (!($result['success'] ?? false)) {
            Log::info('B2B freight webhook processed without state change.', [
                'forwarder' => $forwarder,
                'channel' => $channel,
                'status' => $result['status'] ?? 'unknown',
            ]);

            return response()->json(['message' => $result['message'] ?? 'Webhook ignored.'], 202);
        }

        $this->integrationService->touchLastSync($forwarderModel);

        return response()->json(['message' => $result['message']]);
    }
}
