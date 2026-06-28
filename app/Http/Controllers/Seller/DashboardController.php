<?php

namespace App\Http\Controllers\Seller;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\B2BCompanyService;
use App\Services\B2BDashboardService;
use Auth;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
{
    public function __construct(
        protected B2BDashboardService $b2bDashboardService,
        protected B2BCompanyService $b2bCompanyService
    )
    {
    }

    public function index()
    {
        $authUser = $this->resolveDashboardUser();

        if (!$authUser) {
            flash(translate('No seller account found for dashboard preview.'))->warning();
            return redirect()->route('admin.dashboard');
        }

        $authUserId = $authUser->id;
        $data['this_month_pending_orders'] = OrderDetail::whereSellerId($authUserId)
                                    ->whereDeliveryStatus('pending')
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->whereMonth('created_at', Carbon::now()->month)
                                    ->count();
        $data['this_month_cancelled_orders'] = OrderDetail::whereSellerId($authUserId)
                                    ->whereDeliveryStatus('cancelled')
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->whereMonth('created_at', Carbon::now()->month)
                                    ->count();
        $data['this_month_on_the_way_orders'] = OrderDetail::whereSellerId($authUserId)
                                    ->whereDeliveryStatus('on_the_way')
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->whereMonth('created_at', Carbon::now()->month)
                                    ->count();
        $data['this_month_delivered_orders'] = OrderDetail::whereSellerId($authUserId)
                                    ->whereDeliveryStatus('delivered')
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->whereMonth('created_at', Carbon::now()->month)
                                    ->count();
                                    
        $data['this_month_sold_amount'] = Order::where('seller_id', $authUserId)
                                    ->wherePaymentStatus('paid')
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->whereMonth('created_at', Carbon::now()->month)
                                    ->sum('grand_total');
        $data['previous_month_sold_amount'] = Order::where('seller_id', $authUserId)
                                    ->wherePaymentStatus('paid')
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->whereMonth('created_at', (Carbon::now()->month-1))
                                    ->sum('grand_total');
        
        $data['products'] = filter_products(Product::where('user_id', $authUserId)->orderBy('num_of_sale', 'desc'))->limit(12)->get();
        $data['last_7_days_sales'] = Order::where('created_at', '>=', Carbon::now()->subDays(7))
                                ->where('seller_id', '=', $authUserId)
                                ->where('delivery_status', '=', 'delivered')
                                ->select(DB::raw("sum(grand_total) as total, DATE_FORMAT(created_at, '%d %b') as date"))
                                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"))
                                ->get()->pluck('total', 'date');  
        $data['active_b2b_company'] = $this->b2bCompanyService->getCompanyByUser($authUserId);
        $data['switchable_b2b_companies'] = $this->b2bCompanyService->getSwitchableCompaniesByUser($authUserId);
        $data['b2b_stats'] = $this->b2bDashboardService->sellerStats($authUserId);
        $data['authUser'] = $authUser;

        return view('seller.dashboard', $data);
    }

    protected function resolveDashboardUser(): ?User
    {
        $user = auth()->user();

        if (in_array($user->user_type, ['seller', 'delivery_boy', 'customer'], true)) {
            return $user;
        }

        if (in_array($user->user_type, ['admin', 'staff'], true)) {
            return User::query()
                ->where('user_type', 'seller')
                ->whereHas('shop')
                ->where('banned', 0)
                ->orderBy('id')
                ->first();
        }

        return null;
    }
}
