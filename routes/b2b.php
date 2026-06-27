<?php

use App\Http\Controllers\B2BCompanyController;
use App\Http\Controllers\B2BAuditLogController;
use App\Http\Controllers\B2BAIController;
use App\Http\Controllers\B2BNegotiationController;
use App\Http\Controllers\B2BContainerShipmentController;
use App\Http\Controllers\B2BCustomsDocumentController;
use App\Http\Controllers\B2BFreightForwarderController;
use App\Http\Controllers\B2BInsuranceController;
use App\Http\Controllers\B2BFreightPricingRuleController;
use App\Http\Controllers\B2BFreightQuoteController;
use App\Http\Controllers\B2BHsCodeController;
use App\Http\Controllers\B2BPortController;
use App\Http\Controllers\B2BProformaInvoiceController;
use App\Http\Controllers\B2BPurchaseOrderController;
use App\Http\Controllers\B2BVerificationRequirementController;
use App\Http\Controllers\B2BPackageController;
use App\Http\Controllers\B2BProductPromotionController;
use App\Http\Controllers\B2BPremiumVerificationController;
use App\Http\Controllers\B2BLogisticsChargeSettingController;
use App\Http\Controllers\B2BCompanyMemberController;
use App\Http\Controllers\B2BRfqController;
use App\Http\Controllers\B2BQuotationController;
use App\Http\Controllers\B2BSampleOrderController;
use App\Http\Controllers\B2BShipmentController;
use App\Http\Controllers\B2BShippingProviderController;
use App\Http\Controllers\B2BShippingQuoteController;
use App\Http\Controllers\B2BSupplierDirectoryController;
use App\Http\Controllers\B2BSupplierProfileController;
use App\Http\Controllers\B2BTradeDocumentController;
use App\Http\Controllers\B2BTradeFinanceController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::controller(B2BSupplierDirectoryController::class)->group(function () {
    Route::get('/suppliers', 'index')->name('b2b.suppliers.index');
    Route::get('/suppliers/{slug}', 'show')->name('b2b.suppliers.show');
});

Route::controller(B2BShipmentController::class)->group(function () {
    Route::post('/b2b/carrier-webhooks/{provider}', 'handleWebhook')->name('b2b.carrier-webhooks.handle');
    Route::post('/b2b/carrier-webhooks/{provider}/tracking', 'handleWebhook')->name('b2b.carrier-webhooks.tracking');
    Route::post('/b2b/carrier-webhooks/{provider}/shipment', 'handleWebhook')->name('b2b.carrier-webhooks.shipment');
    Route::post('/b2b/carrier-webhooks/{provider}/pickup', 'handleWebhook')->name('b2b.carrier-webhooks.pickup');
});

Route::controller(B2BContainerShipmentController::class)->group(function () {
    Route::post('/b2b/freight-webhooks/{forwarder}', 'handleWebhook')->name('b2b.freight-webhooks.handle');
    Route::post('/b2b/freight-webhooks/{forwarder}/tracking', 'handleWebhook')->name('b2b.freight-webhooks.tracking');
    Route::post('/b2b/freight-webhooks/{forwarder}/shipment', 'handleWebhook')->name('b2b.freight-webhooks.shipment');
    Route::post('/b2b/freight-webhooks/{forwarder}/pickup', 'handleWebhook')->name('b2b.freight-webhooks.pickup');
    Route::get('/b2b/container-tracking', 'track')->name('b2b.container-tracking.track');
});

Route::group(['middleware' => ['auth', 'verified', 'unbanned']], function () {
    Route::controller(B2BCompanyController::class)->group(function () {
        Route::get('/b2b/company', 'show')->name('b2b.company.show');
        Route::get('/b2b/company/create', 'create')->name('b2b.company.create');
        Route::post('/b2b/company/store', 'store')->name('b2b.company.store');
        Route::get('/b2b/company/edit', 'edit')->name('b2b.company.edit');
        Route::post('/b2b/company/switch', 'switchActiveCompany')->name('b2b.company.switch');
        Route::post('/b2b/company/update', 'update')->name('b2b.company.update');
    });

    Route::controller(B2BPackageController::class)->group(function () {
        Route::get('/b2b/packages', 'companyIndex')->name('b2b.packages.index');
        Route::post('/b2b/packages/{id}/activate-free', 'activateFree')->name('b2b.packages.activate-free');
        Route::post('/b2b/packages/{id}/request', 'requestPaid')->name('b2b.packages.request');
    });

    Route::controller(B2BProductPromotionController::class)->middleware('approved_b2b_company:supplier')->group(function () {
        Route::get('/b2b/sponsored-products', 'companyIndex')->name('seller.b2b.product-promotions.index');
        Route::post('/b2b/sponsored-products/packages/{id}/activate-free', 'activateFree')->name('seller.b2b.product-promotions.activate-free');
        Route::post('/b2b/sponsored-products/packages/{id}/request', 'requestPaid')->name('seller.b2b.product-promotions.request');
        Route::post('/b2b/sponsored-products/products/{productId}/toggle', 'toggleProduct')->name('seller.b2b.product-promotions.toggle-product');
    });

    Route::controller(B2BPremiumVerificationController::class)->middleware('approved_b2b_company:any')->group(function () {
        Route::get('/b2b/premium-verification', 'companyIndex')->name('b2b.premium-verifications.index');
        Route::post('/b2b/premium-verification/packages/{id}/activate-free', 'activateFree')->name('b2b.premium-verifications.activate-free');
        Route::post('/b2b/premium-verification/packages/{id}/request', 'requestPaid')->name('b2b.premium-verifications.request');
    });

    Route::controller(B2BCompanyMemberController::class)->group(function () {
        Route::get('/b2b/company/members', 'index')->name('b2b.company.members.index');
        Route::get('/b2b/company/invitations/{token}/accept', 'acceptInvite')->name('b2b.company.invitations.accept');
        Route::post('/b2b/company/members/{id}/role', 'updateRole')->name('b2b.company.members.update-role');
        Route::post('/b2b/company/members/{id}/suspend', 'suspend')->name('b2b.company.members.suspend');
        Route::post('/b2b/company/members/{id}/remove', 'remove')->name('b2b.company.members.remove');
    });

    Route::controller(B2BCompanyMemberController::class)->middleware('approved_b2b_company:any,package')->group(function () {
        Route::get('/b2b/company/members/invite', 'invite')->name('b2b.company.members.invite');
        Route::post('/b2b/company/members/invite', 'sendInvite')->name('b2b.company.members.send-invite');
    });

    Route::controller(B2BSupplierProfileController::class)->group(function () {
        Route::get('/seller/b2b/company/public-profile', 'edit')->name('seller.b2b.company.public-profile');
        Route::post('/seller/b2b/company/public-profile/update', 'update')->name('seller.b2b.company.public-profile.update');
        Route::post('/seller/b2b/company/public-profile/certifications/store', 'storeCertification')->name('seller.b2b.company.public-profile.certifications.store');
        Route::post('/seller/b2b/company/public-profile/certifications/{id}/update', 'updateCertification')->name('seller.b2b.company.public-profile.certifications.update');
        Route::post('/seller/b2b/company/public-profile/certifications/{id}/delete', 'deleteCertification')->name('seller.b2b.company.public-profile.certifications.delete');
    });

    Route::controller(B2BRfqController::class)->group(function () {
        Route::get('/b2b/rfqs', 'index')->name('b2b.rfqs.index');
        Route::get('/b2b/rfqs/create', 'create')->name('b2b.rfqs.create');
        Route::post('/b2b/rfqs/store', 'store')->name('b2b.rfqs.store');
        Route::get('/b2b/rfqs/{id}', 'show')->name('b2b.rfqs.show');
        Route::get('/b2b/rfqs/{id}/edit', 'edit')->name('b2b.rfqs.edit');
        Route::post('/b2b/rfqs/{id}/update', 'update')->name('b2b.rfqs.update');
        Route::post('/b2b/rfqs/{id}/cancel', 'cancel')->name('b2b.rfqs.cancel');
    });

    Route::controller(B2BAIController::class)->middleware('approved_b2b_company:any')->group(function () {
        Route::get('/b2b/ai', 'dashboard')->name('b2b.ai.dashboard');
        Route::match(['get', 'post'], '/b2b/ai/price-recommendation', 'priceRecommendation')->name('b2b.ai.price-recommendation');
        Route::match(['get', 'post'], '/b2b/ai/supplier-risk', 'supplierRisk')->name('b2b.ai.supplier-risk');
        Route::match(['get', 'post'], '/b2b/ai/buyer-risk', 'buyerRisk')->name('b2b.ai.buyer-risk');
        Route::match(['get', 'post'], '/b2b/ai/freight-recommendation', 'freightRecommendation')->name('b2b.ai.freight-recommendation');
        Route::match(['get', 'post'], '/b2b/ai/currency-analysis', 'currencyAnalysis')->name('b2b.ai.currency-analysis');
        Route::match(['get', 'post'], '/b2b/ai/trade-finance', 'tradeFinanceRecommendation')->name('b2b.ai.trade-finance');
        Route::get('/b2b/ai/opportunities', 'opportunities')->name('b2b.ai.opportunities');
        Route::get('/b2b/ai/notifications', 'notifications')->name('b2b.ai.notifications');
        Route::get('/b2b/ai/dashboard-insights', 'dashboardInsights')->name('b2b.ai.dashboard-insights');
        Route::match(['get', 'post'], '/b2b/ai/rfq-assistant', 'rfqAssistant')->name('b2b.ai.rfq-assistant');
        Route::post('/b2b/ai/rfq-assistant/generate', 'rfqAssistant')->name('b2b.ai.rfq-assistant.generate');
        Route::get('/b2b/ai/rfqs/{id}/supplier-matches', 'supplierMatches')->name('b2b.ai.rfqs.supplier-matches');
        Route::match(['get', 'post'], '/b2b/ai/hs-code', 'hsCode')->name('b2b.ai.hs-code');
        Route::post('/b2b/ai/hs-code/suggest', 'hsCode')->name('b2b.ai.hs-code.suggest');
        Route::get('/b2b/ai/summary/{type}/{id}', 'documentSummary')->name('b2b.ai.summary');
        Route::match(['get', 'post'], '/b2b/ai/trade-assistant', 'tradeAssistant')->name('b2b.ai.trade-assistant');
        Route::post('/b2b/ai/trade-assistant/ask', 'tradeAssistant')->name('b2b.ai.trade-assistant.ask');
    });

    Route::controller(B2BQuotationController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::post('/b2b/quotations/{id}/accept', 'accept')->name('b2b.quotations.accept');
        Route::post('/b2b/quotations/{id}/reject', 'reject')->name('b2b.quotations.reject');
    });

    Route::controller(B2BSampleOrderController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/sample-orders', 'buyerIndex')->name('b2b.sample-orders.index');
        Route::get('/b2b/sample-orders/create', 'create')->name('b2b.sample-orders.create');
        Route::post('/b2b/sample-orders/store', 'store')->name('b2b.sample-orders.store');
        Route::get('/b2b/sample-orders/{id}', 'buyerShow')->name('b2b.sample-orders.show');
        Route::post('/b2b/sample-orders/{id}/pay', 'buyerMarkPaid')->name('b2b.sample-orders.pay');
    });

    Route::controller(B2BShipmentController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/shipments', 'buyerIndex')->name('b2b.shipments.index');
        Route::get('/b2b/shipments/{id}', 'buyerShow')->name('b2b.shipments.show');
    });

    Route::controller(B2BShippingQuoteController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::post('/b2b/shipping-quotes/{id}/select', 'select')->name('b2b.shipping-quotes.select');
    });

    Route::controller(B2BFreightQuoteController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/freight-quotes', 'buyerIndex')->name('b2b.freight-quotes.index');
        Route::get('/b2b/freight-quotes/{id}', 'buyerShow')->name('b2b.freight-quotes.show');
        Route::post('/b2b/freight-quotes/store', 'store')->name('b2b.freight-quotes.store');
        Route::post('/b2b/freight-quotes/{id}/select', 'select')->name('b2b.freight-quotes.select');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/store', 'storeCostLine')->name('b2b.freight-quotes.cost-lines.store');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/{lineId}/update', 'updateCostLine')->name('b2b.freight-quotes.cost-lines.update');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/{lineId}/delete', 'deleteCostLine')->name('b2b.freight-quotes.cost-lines.delete');
    });

    Route::controller(B2BTradeDocumentController::class)->middleware('approved_b2b_company:any,package')->group(function () {
        Route::post('/b2b/trade-documents/{type}/{id}/store', 'store')->name('b2b.trade-documents.store');
        Route::post('/b2b/trade-documents/{id}/delete', 'delete')->name('b2b.trade-documents.delete');
    });

    Route::controller(B2BCustomsDocumentController::class)->middleware('approved_b2b_company:any,package')->group(function () {
        Route::post('/b2b/customs-documents/{type}/{id}/store', 'store')->name('b2b.customs-documents.store');
        Route::post('/b2b/customs-documents/{id}/delete', 'delete')->name('b2b.customs-documents.delete');
    });

    Route::controller(B2BPurchaseOrderController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/purchase-orders', 'buyerIndex')->name('b2b.purchase-orders.index');
        Route::get('/b2b/purchase-orders/{id}', 'buyerShow')->name('b2b.purchase-orders.show');
        Route::post('/b2b/purchase-orders/{id}/cancel', 'buyerCancel')->name('b2b.purchase-orders.cancel');
        Route::post('/b2b/purchase-orders/{id}/complete', 'buyerComplete')->name('b2b.purchase-orders.complete');
    });

    Route::controller(B2BTradeFinanceController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/trade-finance', 'buyerDashboard')->name('b2b.trade-finance.dashboard');
        Route::post('/b2b/purchase-orders/{purchaseOrderId}/milestones', 'configureMilestones')->name('b2b.trade-finance.milestones.store');
        Route::post('/b2b/milestones/{milestoneId}/fund', 'fundMilestone')->name('b2b.trade-finance.milestones.fund');
        Route::post('/b2b/milestones/{milestoneId}/release', 'releaseMilestone')->name('b2b.trade-finance.milestones.release');
        Route::post('/b2b/purchase-orders/{purchaseOrderId}/letters-of-credit', 'requestLetterOfCredit')->name('b2b.trade-finance.letters-of-credit.store');
        Route::post('/b2b/proforma-invoices/{invoiceId}/disputes', 'createDispute')->name('b2b.trade-finance.disputes.store');
        Route::post('/b2b/disputes/{disputeId}/messages', 'addDisputeMessage')->name('b2b.trade-finance.disputes.messages.store');
        Route::post('/b2b/proforma-invoices/{invoiceId}/refunds', 'requestRefund')->name('b2b.trade-finance.refunds.store');
    });

    Route::controller(B2BInsuranceController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/insurance', 'buyerDashboard')->name('b2b.insurance.dashboard');
        Route::post('/b2b/insurance/quotes', 'requestQuote')->name('b2b.insurance.quotes.store');
        Route::post('/b2b/insurance/policies/{policyId}/claims', 'submitClaim')->name('b2b.insurance.claims.store');
        Route::get('/b2b/insurance/policies/{policyId}/export', 'exportPolicy')->name('b2b.insurance.policies.export');
        Route::get('/b2b/insurance/claims/{claimId}/export', 'exportClaim')->name('b2b.insurance.claims.export');
    });

    Route::controller(B2BProformaInvoiceController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/proforma-invoices', 'buyerIndex')->name('b2b.proforma-invoices.index');
        Route::get('/b2b/proforma-invoices/{id}', 'buyerShow')->name('b2b.proforma-invoices.show');
        Route::post('/b2b/proforma-invoices/{id}/accept', 'buyerAccept')->name('b2b.proforma-invoices.accept');
        Route::post('/b2b/proforma-invoices/{id}/fund', 'buyerFund')->name('b2b.proforma-invoices.fund');
        Route::post('/b2b/proforma-invoices/{id}/release', 'buyerRelease')->name('b2b.proforma-invoices.release');
        Route::post('/b2b/proforma-invoices/{id}/dispute', 'buyerDispute')->name('b2b.proforma-invoices.dispute');
    });

    Route::controller(B2BNegotiationController::class)->middleware('approved_b2b_company:buyer,package')->group(function () {
        Route::get('/b2b/negotiations', 'buyerIndex')->name('b2b.negotiations.index');
        Route::get('/b2b/negotiations/{id}', 'buyerShow')->name('b2b.negotiations.show');
        Route::post('/b2b/negotiations/{id}/messages', 'buyerStore')->name('b2b.negotiations.messages.store');
    });
});

Route::group(['prefix' => 'seller', 'middleware' => ['auth', 'verified', 'unbanned']], function () {
    Route::controller(B2BQuotationController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/rfqs', 'supplierRfqIndex')->name('seller.b2b.rfqs.index');
        Route::get('/b2b/rfqs/{id}/quote', 'create')->name('seller.b2b.rfqs.quote');
        Route::post('/b2b/rfqs/{id}/quote', 'store')->name('seller.b2b.rfqs.quote.store');
        Route::get('/b2b/quotations', 'supplierIndex')->name('seller.b2b.quotations.index');
        Route::get('/b2b/quotations/{id}', 'show')->name('seller.b2b.quotations.show');
        Route::get('/b2b/quotations/{id}/edit', 'edit')->name('seller.b2b.quotations.edit');
        Route::post('/b2b/quotations/{id}/update', 'update')->name('seller.b2b.quotations.update');
        Route::post('/b2b/quotations/{id}/withdraw', 'withdraw')->name('seller.b2b.quotations.withdraw');
    });

    Route::controller(B2BPurchaseOrderController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/purchase-orders', 'supplierIndex')->name('seller.b2b.purchase-orders.index');
        Route::get('/b2b/purchase-orders/{id}', 'supplierShow')->name('seller.b2b.purchase-orders.show');
        Route::post('/b2b/purchase-orders/{id}/accept', 'supplierAccept')->name('seller.b2b.purchase-orders.accept');
        Route::post('/b2b/purchase-orders/{id}/reject', 'supplierReject')->name('seller.b2b.purchase-orders.reject');
    });

    Route::controller(B2BProformaInvoiceController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/proforma-invoices', 'supplierIndex')->name('seller.b2b.proforma-invoices.index');
        Route::get('/b2b/purchase-orders/{purchaseOrderId}/proforma-invoices/create', 'create')->name('seller.b2b.proforma-invoices.create');
        Route::post('/b2b/purchase-orders/{purchaseOrderId}/proforma-invoices/store', 'store')->name('seller.b2b.proforma-invoices.store');
        Route::get('/b2b/proforma-invoices/{id}', 'supplierShow')->name('seller.b2b.proforma-invoices.show');
        Route::post('/b2b/proforma-invoices/{id}/send', 'supplierSend')->name('seller.b2b.proforma-invoices.send');
        Route::post('/b2b/proforma-invoices/{id}/cancel', 'supplierCancel')->name('seller.b2b.proforma-invoices.cancel');
    });

    Route::controller(B2BSampleOrderController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/sample-orders', 'supplierIndex')->name('seller.b2b.sample-orders.index');
        Route::get('/b2b/sample-orders/{id}', 'supplierShow')->name('seller.b2b.sample-orders.show');
        Route::post('/b2b/sample-orders/{id}/accept', 'supplierAccept')->name('seller.b2b.sample-orders.accept');
        Route::post('/b2b/sample-orders/{id}/reject', 'supplierReject')->name('seller.b2b.sample-orders.reject');
    });

    Route::controller(B2BShippingQuoteController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/purchase-orders/{purchaseOrderId}/shipping-quotes/create', 'createForPurchaseOrder')->name('seller.b2b.shipping-quotes.purchase-orders.create');
        Route::post('/b2b/purchase-orders/{purchaseOrderId}/shipping-quotes/store', 'storeForPurchaseOrder')->name('seller.b2b.shipping-quotes.purchase-orders.store');
        Route::get('/b2b/sample-orders/{sampleOrderId}/shipping-quotes/create', 'createForSampleOrder')->name('seller.b2b.shipping-quotes.sample-orders.create');
        Route::post('/b2b/sample-orders/{sampleOrderId}/shipping-quotes/store', 'storeForSampleOrder')->name('seller.b2b.shipping-quotes.sample-orders.store');
        Route::post('/b2b/shipping-providers/{providerId}/rates', 'lookupRates')->name('seller.b2b.shipping-providers.rates');
    });

    Route::controller(B2BShipmentController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/shipments', 'supplierIndex')->name('seller.b2b.shipments.index');
        Route::get('/b2b/shipments/create', 'create')->name('seller.b2b.shipments.create');
        Route::post('/b2b/shipments/store', 'store')->name('seller.b2b.shipments.store');
        Route::get('/b2b/shipments/{id}', 'supplierShow')->name('seller.b2b.shipments.show');
        Route::post('/b2b/shipments/{id}/tracking', 'updateTracking')->name('seller.b2b.shipments.tracking');
        Route::post('/b2b/shipments/{id}/sync', 'sync')->name('seller.b2b.shipments.sync');
        Route::post('/b2b/shipments/{id}/status', 'updateStatus')->name('seller.b2b.shipments.status');
        Route::post('/b2b/shipments/{id}/carrier/create', 'createCarrierShipment')->name('seller.b2b.shipments.carrier.create');
        Route::post('/b2b/shipments/{id}/carrier/pickup', 'requestPickup')->name('seller.b2b.shipments.carrier.pickup');
        Route::post('/b2b/shipments/{id}/carrier/label', 'generateLabel')->name('seller.b2b.shipments.carrier.label');
        Route::post('/b2b/shipments/{id}/carrier/cancel', 'cancelCarrierShipment')->name('seller.b2b.shipments.carrier.cancel');
    });

    Route::controller(B2BContainerShipmentController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::post('/b2b/freight-quotes/{quoteId}/container-shipments/store', 'createFromQuote')->name('seller.b2b.container-shipments.store');
        Route::post('/b2b/container-shipments/{id}/sync', 'sync')->name('seller.b2b.container-shipments.sync');
    });

    Route::controller(B2BFreightQuoteController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/freight-quotes', 'supplierIndex')->name('seller.b2b.freight-quotes.index');
        Route::get('/b2b/freight-quotes/{id}', 'supplierShow')->name('seller.b2b.freight-quotes.show');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/store', 'storeCostLine')->name('seller.b2b.freight-quotes.cost-lines.store');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/{lineId}/update', 'updateCostLine')->name('seller.b2b.freight-quotes.cost-lines.update');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/{lineId}/delete', 'deleteCostLine')->name('seller.b2b.freight-quotes.cost-lines.delete');
    });

    Route::controller(B2BNegotiationController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/negotiations', 'supplierIndex')->name('seller.b2b.negotiations.index');
        Route::get('/b2b/negotiations/{id}', 'supplierShow')->name('seller.b2b.negotiations.show');
        Route::post('/b2b/negotiations/{id}/messages', 'supplierStore')->name('seller.b2b.negotiations.messages.store');
    });

    Route::controller(B2BTradeFinanceController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/trade-finance', 'supplierDashboard')->name('seller.b2b.trade-finance.dashboard');
        Route::post('/b2b/escrows/{escrowId}/settlements', 'requestSettlement')->name('seller.b2b.trade-finance.settlements.store');
        Route::post('/b2b/proforma-invoices/{invoiceId}/disputes', 'createDispute')->name('seller.b2b.trade-finance.disputes.store');
        Route::post('/b2b/disputes/{disputeId}/messages', 'addDisputeMessage')->name('seller.b2b.trade-finance.disputes.messages.store');
        Route::post('/b2b/proforma-invoices/{invoiceId}/refunds', 'requestRefund')->name('seller.b2b.trade-finance.refunds.store');
        Route::post('/b2b/letters-of-credit/{lcId}/status', 'updateLetterOfCreditStatus')->name('seller.b2b.trade-finance.letters-of-credit.status');
    });

    Route::controller(B2BInsuranceController::class)->middleware('approved_b2b_company:supplier,package')->group(function () {
        Route::get('/b2b/insurance', 'supplierDashboard')->name('seller.b2b.insurance.dashboard');
        Route::post('/b2b/insurance/quotes', 'requestQuote')->name('seller.b2b.insurance.quotes.store');
        Route::post('/b2b/insurance/policies/{policyId}/claims', 'submitClaim')->name('seller.b2b.insurance.claims.store');
        Route::get('/b2b/insurance/policies/{policyId}/export', 'exportPolicy')->name('seller.b2b.insurance.policies.export');
        Route::get('/b2b/insurance/claims/{claimId}/export', 'exportClaim')->name('seller.b2b.insurance.claims.export');
    });
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/b2b/dashboard', [AdminController::class, 'b2b_dashboard'])->name('admin.b2b.dashboard');
    Route::get('/b2b/trade-finance', [B2BTradeFinanceController::class, 'adminDashboard'])->name('admin.b2b.trade-finance.dashboard');
    Route::get('/b2b/insurance', [B2BInsuranceController::class, 'adminDashboard'])->name('admin.b2b.insurance.dashboard');
    Route::post('/b2b/insurance/providers', [B2BInsuranceController::class, 'storeProvider'])->name('admin.b2b.insurance.providers.store');
    Route::post('/b2b/insurance/providers/{providerId}/update', [B2BInsuranceController::class, 'updateProvider'])->name('admin.b2b.insurance.providers.update');
    Route::post('/b2b/insurance/quotes/{quoteId}/issue-policy', [B2BInsuranceController::class, 'issuePolicy'])->name('admin.b2b.insurance.policies.issue');
    Route::post('/b2b/insurance/claims/{claimId}/status', [B2BInsuranceController::class, 'updateClaimStatus'])->name('admin.b2b.insurance.claims.status');
    Route::post('/b2b/insurance/payments', [B2BInsuranceController::class, 'recordPayment'])->name('admin.b2b.insurance.payments.store');
    Route::get('/b2b/insurance/policies/{policyId}/export', [B2BInsuranceController::class, 'exportPolicy'])->name('admin.b2b.insurance.policies.export');
    Route::get('/b2b/insurance/claims/{claimId}/export', [B2BInsuranceController::class, 'exportClaim'])->name('admin.b2b.insurance.claims.export');
    Route::post('/b2b/disputes/{disputeId}/resolve', [B2BTradeFinanceController::class, 'resolveDispute'])->name('admin.b2b.trade-finance.disputes.resolve');
    Route::post('/b2b/settlements/{settlementId}/approve', [B2BTradeFinanceController::class, 'approveSettlement'])->name('admin.b2b.trade-finance.settlements.approve');
    Route::post('/b2b/settlements/{settlementId}/complete', [B2BTradeFinanceController::class, 'completeSettlement'])->name('admin.b2b.trade-finance.settlements.complete');
    Route::post('/b2b/refunds/{refundId}/approve', [B2BTradeFinanceController::class, 'approveRefund'])->name('admin.b2b.trade-finance.refunds.approve');
    Route::post('/b2b/refunds/{refundId}/complete', [B2BTradeFinanceController::class, 'completeRefund'])->name('admin.b2b.trade-finance.refunds.complete');
    Route::post('/b2b/letters-of-credit/{lcId}/status', [B2BTradeFinanceController::class, 'updateLetterOfCreditStatus'])->name('admin.b2b.trade-finance.letters-of-credit.status');

    Route::controller(B2BCompanyController::class)->group(function () {
        Route::get('/b2b/companies/verification', 'adminVerificationIndex')->name('admin.b2b.companies.verification');
        Route::get('/b2b/companies/{id}/verification', 'adminVerificationShow')->name('admin.b2b.companies.verification.show');
        Route::get('/b2b/companies/create', 'adminCreate')->name('admin.b2b.companies.create');
        Route::post('/b2b/companies/store', 'adminStore')->name('admin.b2b.companies.store');
        Route::get('/b2b/companies', 'adminIndex')->name('admin.b2b.companies.index');
        Route::get('/b2b/companies/{id}', 'adminShow')->name('admin.b2b.companies.show');
        Route::post('/b2b/companies/{id}/approve', 'approve')->name('admin.b2b.companies.approve');
        Route::post('/b2b/companies/{id}/reject', 'reject')->name('admin.b2b.companies.reject');
        Route::post('/b2b/companies/{id}/supplier-controls', 'updateSupplierControls')->name('admin.b2b.companies.supplier-controls');
        Route::post('/b2b/certifications/{id}/approve', 'approveCertification')->name('admin.b2b.certifications.approve');
        Route::post('/b2b/certifications/{id}/reject', 'rejectCertification')->name('admin.b2b.certifications.reject');
    });

    Route::controller(B2BVerificationRequirementController::class)->group(function () {
        Route::get('/b2b/verification-requirements', 'index')->name('admin.b2b.verification-requirements.index');
        Route::post('/b2b/verification-requirements/store', 'store')->name('admin.b2b.verification-requirements.store');
        Route::post('/b2b/verification-requirements/{id}/update', 'update')->name('admin.b2b.verification-requirements.update');
        Route::post('/b2b/verification-requirements/{id}/delete', 'destroy')->name('admin.b2b.verification-requirements.delete');
    });

    Route::controller(B2BPackageController::class)->group(function () {
        Route::get('/b2b/packages', 'adminIndex')->name('admin.b2b.packages.index');
        Route::get('/b2b/packages/create', 'adminCreate')->name('admin.b2b.packages.create');
        Route::post('/b2b/packages/store', 'adminStore')->name('admin.b2b.packages.store');
        Route::get('/b2b/packages/{id}/edit', 'adminEdit')->name('admin.b2b.packages.edit');
        Route::post('/b2b/packages/{id}/update', 'adminUpdate')->name('admin.b2b.packages.update');
        Route::post('/b2b/packages/{id}/delete', 'adminDestroy')->name('admin.b2b.packages.delete');
        Route::get('/b2b/package-requests', 'adminRequests')->name('admin.b2b.package-requests.index');
        Route::post('/b2b/package-requests/{id}/approve', 'approveRequest')->name('admin.b2b.package-requests.approve');
        Route::post('/b2b/package-requests/{id}/reject', 'rejectRequest')->name('admin.b2b.package-requests.reject');
        Route::get('/b2b/supplier-featured-packages', 'adminFeaturedSupplierIndex')->name('admin.b2b.featured-packages.index');
        Route::get('/b2b/supplier-featured-packages/create', 'adminFeaturedSupplierCreate')->name('admin.b2b.featured-packages.create');
        Route::post('/b2b/supplier-featured-packages/store', 'adminStore')->name('admin.b2b.featured-packages.store');
        Route::get('/b2b/supplier-featured-packages/{id}/edit', 'adminFeaturedSupplierEdit')->name('admin.b2b.featured-packages.edit');
        Route::post('/b2b/supplier-featured-packages/{id}/update', 'adminUpdate')->name('admin.b2b.featured-packages.update');
        Route::post('/b2b/supplier-featured-packages/{id}/delete', 'adminDestroy')->name('admin.b2b.featured-packages.delete');
        Route::get('/b2b/supplier-featured-package-requests', 'adminFeaturedSupplierRequests')->name('admin.b2b.featured-package-requests.index');
        Route::post('/b2b/supplier-featured-package-requests/{id}/approve', 'approveRequest')->name('admin.b2b.featured-package-requests.approve');
        Route::post('/b2b/supplier-featured-package-requests/{id}/reject', 'rejectRequest')->name('admin.b2b.featured-package-requests.reject');
    });

    Route::controller(B2BProductPromotionController::class)->group(function () {
        Route::get('/b2b/product-promotion-packages', 'adminIndex')->name('admin.b2b.product-promotions.index');
        Route::get('/b2b/product-promotion-packages/create', 'adminCreate')->name('admin.b2b.product-promotions.create');
        Route::post('/b2b/product-promotion-packages/store', 'adminStore')->name('admin.b2b.product-promotions.store');
        Route::get('/b2b/product-promotion-packages/{id}/edit', 'adminEdit')->name('admin.b2b.product-promotions.edit');
        Route::post('/b2b/product-promotion-packages/{id}/update', 'adminUpdate')->name('admin.b2b.product-promotions.update');
        Route::post('/b2b/product-promotion-packages/{id}/delete', 'adminDestroy')->name('admin.b2b.product-promotions.delete');
        Route::get('/b2b/product-promotion-package-requests', 'adminRequests')->name('admin.b2b.product-promotions.requests');
        Route::post('/b2b/product-promotion-package-requests/{id}/approve', 'approveRequest')->name('admin.b2b.product-promotions.requests.approve');
        Route::post('/b2b/product-promotion-package-requests/{id}/reject', 'rejectRequest')->name('admin.b2b.product-promotions.requests.reject');
    });

    Route::controller(B2BPremiumVerificationController::class)->group(function () {
        Route::get('/b2b/premium-verification-packages', 'adminIndex')->name('admin.b2b.premium-verifications.index');
        Route::get('/b2b/premium-verification-packages/create', 'adminCreate')->name('admin.b2b.premium-verifications.create');
        Route::post('/b2b/premium-verification-packages/store', 'adminStore')->name('admin.b2b.premium-verifications.store');
        Route::get('/b2b/premium-verification-packages/{id}/edit', 'adminEdit')->name('admin.b2b.premium-verifications.edit');
        Route::post('/b2b/premium-verification-packages/{id}/update', 'adminUpdate')->name('admin.b2b.premium-verifications.update');
        Route::post('/b2b/premium-verification-packages/{id}/delete', 'adminDestroy')->name('admin.b2b.premium-verifications.delete');
        Route::get('/b2b/premium-verification-requests', 'adminRequests')->name('admin.b2b.premium-verifications.requests');
        Route::post('/b2b/premium-verification-requests/{id}/approve', 'approveRequest')->name('admin.b2b.premium-verifications.requests.approve');
        Route::post('/b2b/premium-verification-requests/{id}/reject', 'rejectRequest')->name('admin.b2b.premium-verifications.requests.reject');
    });

    Route::controller(B2BLogisticsChargeSettingController::class)->group(function () {
        Route::get('/b2b/logistics-charge-settings', 'index')->name('admin.b2b.logistics-charge-settings.index');
        Route::post('/b2b/logistics-charge-settings/update', 'update')->name('admin.b2b.logistics-charge-settings.update');
    });

    Route::controller(B2BRfqController::class)->group(function () {
        Route::get('/b2b/rfqs', 'adminIndex')->name('admin.b2b.rfqs.index');
        Route::get('/b2b/rfqs/{id}', 'adminShow')->name('admin.b2b.rfqs.show');
        Route::post('/b2b/rfqs/{id}/close', 'close')->name('admin.b2b.rfqs.close');
    });

    Route::controller(B2BPurchaseOrderController::class)->group(function () {
        Route::get('/b2b/purchase-orders', 'adminIndex')->name('admin.b2b.purchase-orders.index');
        Route::get('/b2b/purchase-orders/{id}', 'adminShow')->name('admin.b2b.purchase-orders.show');
    });

    Route::controller(B2BProformaInvoiceController::class)->group(function () {
        Route::get('/b2b/proforma-invoices', 'adminIndex')->name('admin.b2b.proforma-invoices.index');
        Route::get('/b2b/proforma-invoices/{id}', 'adminShow')->name('admin.b2b.proforma-invoices.show');
        Route::post('/b2b/proforma-invoices/{id}/release', 'adminRelease')->name('admin.b2b.proforma-invoices.release');
        Route::post('/b2b/proforma-invoices/{id}/refund', 'adminRefund')->name('admin.b2b.proforma-invoices.refund');
    });

    Route::controller(B2BShippingProviderController::class)->group(function () {
        Route::get('/b2b/shipping-providers', 'index')->name('admin.b2b.shipping-providers.index');
        Route::post('/b2b/shipping-providers/store', 'store')->name('admin.b2b.shipping-providers.store');
        Route::post('/b2b/shipping-providers/{id}/update', 'update')->name('admin.b2b.shipping-providers.update');
        Route::post('/b2b/shipping-providers/{id}/test', 'testConnection')->name('admin.b2b.shipping-providers.test');
        Route::post('/b2b/shipping-providers/{id}/test-authentication', 'testAuthentication')->name('admin.b2b.shipping-providers.test-authentication');
        Route::post('/b2b/shipping-providers/{id}/verify-credentials', 'verifyCredentials')->name('admin.b2b.shipping-providers.verify-credentials');
        Route::post('/b2b/shipping-providers/{id}/test-webhook', 'testWebhook')->name('admin.b2b.shipping-providers.test-webhook');
        Route::post('/b2b/shipping-providers/{id}/send-sample-webhook', 'sendSampleWebhook')->name('admin.b2b.shipping-providers.send-sample-webhook');
        Route::post('/b2b/shipping-providers/{id}/regenerate-secret', 'regenerateSecret')->name('admin.b2b.shipping-providers.regenerate-secret');
        Route::post('/b2b/shipping-providers/{id}/integration-events', 'updateIntegrationEvents')->name('admin.b2b.shipping-providers.integration-events');
    });

    Route::controller(B2BPortController::class)->group(function () {
        Route::get('/b2b/ports', 'adminIndex')->name('admin.b2b.ports.index');
        Route::post('/b2b/ports/store', 'store')->name('admin.b2b.ports.store');
        Route::post('/b2b/ports/{id}/update', 'update')->name('admin.b2b.ports.update');
        Route::get('/b2b/ports/export', 'exportCsv')->name('admin.b2b.ports.export');
        Route::post('/b2b/ports/import', 'importCsv')->name('admin.b2b.ports.import');
    });

    Route::controller(B2BFreightForwarderController::class)->group(function () {
        Route::get('/b2b/freight-forwarders', 'adminIndex')->name('admin.b2b.freight-forwarders.index');
        Route::post('/b2b/freight-forwarders/store', 'store')->name('admin.b2b.freight-forwarders.store');
        Route::post('/b2b/freight-forwarders/{id}/update', 'update')->name('admin.b2b.freight-forwarders.update');
        Route::post('/b2b/freight-forwarders/{id}/test', 'testConnection')->name('admin.b2b.freight-forwarders.test');
        Route::post('/b2b/freight-forwarders/{id}/test-authentication', 'testAuthentication')->name('admin.b2b.freight-forwarders.test-authentication');
        Route::post('/b2b/freight-forwarders/{id}/verify-credentials', 'verifyCredentials')->name('admin.b2b.freight-forwarders.verify-credentials');
        Route::post('/b2b/freight-forwarders/{id}/test-webhook', 'testWebhook')->name('admin.b2b.freight-forwarders.test-webhook');
        Route::post('/b2b/freight-forwarders/{id}/send-sample-webhook', 'sendSampleWebhook')->name('admin.b2b.freight-forwarders.send-sample-webhook');
        Route::post('/b2b/freight-forwarders/{id}/regenerate-secret', 'regenerateSecret')->name('admin.b2b.freight-forwarders.regenerate-secret');
        Route::post('/b2b/freight-forwarders/{id}/integration-events', 'updateIntegrationEvents')->name('admin.b2b.freight-forwarders.integration-events');
    });

    Route::controller(B2BFreightQuoteController::class)->group(function () {
        Route::get('/b2b/freight-quotes', 'adminIndex')->name('admin.b2b.freight-quotes.index');
        Route::get('/b2b/freight-quotes/{id}', 'adminShow')->name('admin.b2b.freight-quotes.show');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/store', 'storeCostLine')->name('admin.b2b.freight-quotes.cost-lines.store');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/{lineId}/update', 'updateCostLine')->name('admin.b2b.freight-quotes.cost-lines.update');
        Route::post('/b2b/freight-quotes/{id}/cost-lines/{lineId}/delete', 'deleteCostLine')->name('admin.b2b.freight-quotes.cost-lines.delete');
    });

    Route::controller(B2BFreightPricingRuleController::class)->group(function () {
        Route::get('/b2b/freight-pricing-rules', 'index')->name('admin.b2b.freight-pricing-rules.index');
        Route::post('/b2b/freight-pricing-rules/store', 'store')->name('admin.b2b.freight-pricing-rules.store');
        Route::post('/b2b/freight-pricing-rules/{id}/update', 'update')->name('admin.b2b.freight-pricing-rules.update');
    });

    Route::controller(B2BHsCodeController::class)->group(function () {
        Route::get('/b2b/hs-codes', 'index')->name('admin.b2b.hs-codes.index');
        Route::post('/b2b/hs-codes/store', 'store')->name('admin.b2b.hs-codes.store');
        Route::post('/b2b/hs-codes/{id}/update', 'update')->name('admin.b2b.hs-codes.update');
    });

    Route::controller(B2BShippingQuoteController::class)->group(function () {
        Route::get('/b2b/shipping-quotes', 'adminIndex')->name('admin.b2b.shipping-quotes.index');
    });

    Route::controller(B2BSampleOrderController::class)->group(function () {
        Route::get('/b2b/sample-orders', 'adminIndex')->name('admin.b2b.sample-orders.index');
        Route::get('/b2b/sample-orders/{id}', 'adminShow')->name('admin.b2b.sample-orders.show');
    });

    Route::controller(B2BShipmentController::class)->group(function () {
        Route::get('/b2b/shipments', 'adminIndex')->name('admin.b2b.shipments.index');
        Route::get('/b2b/shipments/{id}', 'adminShow')->name('admin.b2b.shipments.show');
        Route::post('/b2b/shipments/{id}/sync', 'adminSync')->name('admin.b2b.shipments.sync');
    });

    Route::controller(B2BNegotiationController::class)->group(function () {
        Route::get('/b2b/negotiations', 'adminIndex')->name('admin.b2b.negotiations.index');
        Route::get('/b2b/negotiations/{id}', 'adminShow')->name('admin.b2b.negotiations.show');
    });

    Route::controller(B2BAuditLogController::class)->group(function () {
        Route::get('/b2b/audit-logs', 'adminIndex')->name('admin.b2b.audit-logs.index');
    });
});
