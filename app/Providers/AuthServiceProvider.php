<?php

namespace App\Providers;

use App\Models\B2BNegotiation;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Policies\B2BNegotiationPolicy;
use App\Policies\B2BProformaInvoicePolicy;
use App\Policies\B2BPurchaseOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
  /**
   * The policy mappings for the application.
   *
   * @var array
   */
  protected $policies = [
    B2BPurchaseOrder::class => B2BPurchaseOrderPolicy::class,
    B2BProformaInvoice::class => B2BProformaInvoicePolicy::class,
    B2BNegotiation::class => B2BNegotiationPolicy::class,
  ];

  /**
   * Register any authentication / authorization services.
   *
   * @return void
   */
  public function boot()
  {
    $this->registerPolicies();

    // Implicitly grant "Super Admin" role all permissions
    Gate::before(function ($user, $ability) {
      return $user->hasRole('Super Admin') ? true : null;
    });
  }
}
