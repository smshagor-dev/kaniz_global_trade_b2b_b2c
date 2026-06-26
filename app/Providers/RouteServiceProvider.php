<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
  /**
   * This namespace is applied to your controller routes.
   *
   * In addition, it is set as the URL generator's root namespace.
   *
   * @var string
   */
   protected $namespace = null;

  /**
   * Define your route model bindings, pattern filters, etc.
   *
   * @return void
   */
  public function boot()
  {
    //

    parent::boot();

    $this->configureRateLimiting();
  }

  /**
   * Define the routes for the application.
   *
   * @return void
   */
  public function map()
  {
    $this->mapApiRoutes();

    $this->mapApiSellerRoutes();

    $this->mapAdminRoutes();

    $this->mapSellerRoutes();

    $this->mapAffiliateRoutes();

    $this->mapRefundRoutes();

    $this->mapClubPointsRoutes();

    $this->mapOtpRoutes();

    $this->mapOfflinePaymentRoutes();

    $this->mapAfricanPaymentGatewayRoutes();

    $this->mapPaytmRoutes();

    $this->mapPosRoutes();

    $this->mapSellerPackageRoutes();

    $this->mapDeliveryBoyRoutes();

    $this->mapAuctionRoutes();

    $this->mapWholesaleRoutes();

    $this->mapB2BRoutes();

    $this->mapPreorderRoutes();

    $this->mapCybersourceRoutes();

    $this->mapGstRoutes();

    $this->mapShiprocketRoutes();

    $this->mapSteadfastRoutes();

    $this->mapPathaoRoutes();

    $this->mapKnetRoutes();

    $this->mapUddoktapayRoutes();

    $this->mapRedxRoutes();
    
    $this->mapWebRoutes();

    // $this->mapInstallRoutes();

    // $this->mapUpdateRoutes();
  }

  /**
   * Define the "b2b" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapWholesaleRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/wholesale.php'));
  }

  protected function mapB2BRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/b2b.php'));
  }

  /**
   * Define the "delivery boy" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapDeliveryBoyRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/delivery_boy.php', [
      \App\Http\Controllers\DeliveryBoyController::class,
    ]);
  }

    /**
   * Define the "auction" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAuctionRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/auction.php', [
      \App\Http\Controllers\AuctionProductController::class,
      \App\Http\Controllers\AuctionProductBidController::class,
    ]);
  }

  /**
   * Define the "seller package" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapSellerPackageRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/seller_package.php', [
      \App\Http\Controllers\SellerPackageController::class,
      \App\Http\Controllers\SellerPackagePaymentController::class,
    ]);
  }

  /**
   * Define the "affiliate" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAffiliateRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/affiliate.php', [
      \App\Http\Controllers\AffiliateController::class,
    ]);
  }

  /**
   * Define the "offline payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapOfflinePaymentRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/offline_payment.php', [
      \App\Http\Controllers\OfflinePayoutMethodController::class,
      \App\Http\Controllers\ManualPaymentMethodController::class,
    ]);
  }


  /**
   * Define the "Asian payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapPaytmRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/paytm.php', [
      \App\Http\Controllers\Payment\PaytmController::class,
      \App\Http\Controllers\Payment\ToyyibpayController::class,
      \App\Http\Controllers\Payment\KhaltiController::class,
      \App\Http\Controllers\Payment\PhonepeController::class,
    ]);
  }

  /**
   * Define the "African payment" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAfricanPaymentGatewayRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/african_pg.php', [
      \App\Http\Controllers\AfricanPaymentGatewayController::class,
      \App\Http\Controllers\Payment\FlutterwaveController::class,
      \App\Http\Controllers\Payment\MpesaController::class,
      \App\Http\Controllers\Payment\PayfastController::class,
    ]);
  }

  /**
   * Define the "refund" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapRefundRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/refund_request.php', [
      \App\Http\Controllers\RefundRequestController::class,
      \App\Http\Controllers\RefundReasonController::class,
    ]);
  }

  /**
   * Define the "club points" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapClubPointsRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/club_points.php', [
      \App\Http\Controllers\ClubPointController::class,
    ]);
  }

  /**
   * Define the "OTP System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapOtpRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/otp.php', [
      \App\Http\Controllers\OTPVerificationController::class,
      \App\Http\Controllers\OTPController::class,
      \App\Http\Controllers\SmsController::class,
      \App\Http\Controllers\SmsTemplateController::class,
    ]);
  }

  /**
   * Define the "POS System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapPosRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/pos.php', [
      \App\Http\Controllers\PosController::class,
      \App\Http\Controllers\Seller\PosController::class,
    ]);
  }

  /**
   * Define the "updating" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapUpdateRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/update.php'));
  }

  /**
   * Define the "installation" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapInstallRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/install.php'));
  }

  /**
   * Define the "web" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapWebRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/web.php'));
  }

  /**
   * Define the "admin" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapAdminRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/admin.php'));
  }

  /**
   * Define the "seller" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapSellerRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/seller.php'));
  }

	 /**
     * Define the "Pre Order" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapPreorderRoutes()
    {
        $this->mapOptionalWebRouteFile('routes/preorder.php', [
            \App\Http\Controllers\Preorder\DashboardController::class,
            \App\Http\Controllers\Preorder\seller\DashboardController::class,
        ]);
    }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiSellerRoutes()
  {
    Route::prefix('api')
       ->middleware('api')
       ->namespace($this->namespace)
       ->group(base_path('routes/api_seller.php'));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiRoutes()
  {
    Route::prefix('api')
       ->middleware('api')
       ->namespace($this->namespace)
       ->group(base_path('routes/api.php'));
  }


  /**
   * Define the "b2b" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapCybersourceRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/cybersource.php'));
  }

  /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(600)->by(optional($request->user())->id ?: $request->ip());
        });
    }

  /**
   * Define the "GST System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapGstRoutes()
  {
    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path('routes/gst.php'));
  }

  /**
   * Define the "Shiprocket System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapShiprocketRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/shiprocket.php', [
      \App\Http\Controllers\ShiprocketController::class,
    ]);
  }

  /**
   * Define the "Steadfast System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapSteadfastRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/steadfast.php', [
      \App\Http\Controllers\SteadfastController::class,
    ]);
  }

  /**
   * Define the "Pathao System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapPathaoRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/pathao.php', [
      \App\Http\Controllers\PathaoController::class,
    ]);
  }

    /**
   * Define the "Knet Payment Gateway" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapKnetRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/knet.php', [
      \App\Http\Controllers\Payment\KnetController::class,
    ]);
  }

  /**
   * Define the "Uddoktapay" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapUddoktapayRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/uddoktapay.php', [
      \App\Http\Controllers\Payment\UddoktapayController::class,
    ]);
  }
  
    /**
   * Define the "Redx System" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapRedxRoutes()
  {
    $this->mapOptionalWebRouteFile('routes/redx.php', [
      \App\Http\Controllers\RedxController::class,
    ]);
  }

  protected function mapOptionalWebRouteFile(string $routeFile, array $controllerClasses): void
  {
    foreach ($controllerClasses as $controllerClass) {
      if (!class_exists($controllerClass)) {
        return;
      }
    }

    Route::middleware('web')
       ->namespace($this->namespace)
       ->group(base_path($routeFile));
  }
}
