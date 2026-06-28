<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Cart;
use App\Notifications\EmailVerificationNotification;
use App\Traits\PreventDemoModeChanges;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasApiTokens, HasRoles;


    public function sendEmailVerificationNotification()
    {
        $this->notify(new EmailVerificationNotification());
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'address', 'city', 'postal_code', 'phone', 'country', 'provider_id', 'email_verified_at', 'verification_code', 'verification_status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function affiliate_user()
    {
        return $this->hasOne(AffiliateUser::class);
    }

    public function affiliate_withdraw_request()
    {
        return $this->hasMany(AffiliateWithdrawRequest::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shop()
    {
        return $this->hasOne(Shop::class);
    }
    public function seller()
    {
        return $this->hasOne(Seller::class);
    }


    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function seller_orders()
    {
        return $this->hasMany(Order::class, "seller_id");
    }
    public function seller_sales()
    {
        return $this->hasMany(OrderDetail::class, "seller_id");
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class)->orderBy('created_at', 'desc');
    }

    public function club_point()
    {
        return $this->hasOne(ClubPoint::class);
    }

    public function customer_package()
    {
        return $this->belongsTo(CustomerPackage::class);
    }

    public function customer_package_payments()
    {
        return $this->hasMany(CustomerPackagePayment::class);
    }

    public function customer_products()
    {
        return $this->hasMany(CustomerProduct::class);
    }

    public function seller_package_payments()
    {
        return $this->hasMany(SellerPackagePayment::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function payment_informations()
    {
        return $this->hasMany(PaymentInformation::class);
    }

    public function affiliate_log()
    {
        return $this->hasMany(AffiliateLog::class);
    }

    public function product_bids()
    {
        return $this->hasMany(AuctionProductBid::class);
    }

    public function product_queries(){
        return $this->hasMany(ProductQuery::class,'customer_id');
    }

    public function uploads(){
        return $this->hasMany(Upload::class);
    }

    public function userCoupon(){
        return $this->hasOne(UserCoupon::class);
    }

    public function preorderProducts()
    {
        return $this->hasMany(PreorderProduct::class);
    }
    public function preorders()
    {
        return $this->hasMany(Preorder::class);
    }

    public function b2bCompany()
    {
        return $this->hasOne(B2BCompany::class);
    }

    public function b2bCompanyMemberships()
    {
        return $this->hasMany(B2BCompanyMember::class, 'user_id');
    }

    public function b2bCompanyInvitations()
    {
        return $this->hasMany(B2BCompanyInvitation::class, 'invited_by');
    }

    public function b2bRfqs()
    {
        return $this->hasMany(B2BRfq::class);
    }

    public function b2bQuotations()
    {
        return $this->hasMany(B2BQuotation::class, 'supplier_user_id');
    }

    public function buyerPurchaseOrders()
    {
        return $this->hasMany(B2BPurchaseOrder::class, 'buyer_user_id');
    }

    public function supplierPurchaseOrders()
    {
        return $this->hasMany(B2BPurchaseOrder::class, 'supplier_user_id');
    }

    public function buyerProformaInvoices()
    {
        return $this->hasMany(B2BProformaInvoice::class, 'buyer_user_id');
    }

    public function supplierProformaInvoices()
    {
        return $this->hasMany(B2BProformaInvoice::class, 'supplier_user_id');
    }

    public function buyerNegotiations()
    {
        return $this->hasMany(B2BNegotiation::class, 'buyer_user_id');
    }

    public function supplierNegotiations()
    {
        return $this->hasMany(B2BNegotiation::class, 'supplier_user_id');
    }

    public function b2bAuditLogs()
    {
        return $this->hasMany(B2BAuditLog::class, 'actor_user_id');
    }

    public function fraudChecks()
    {
        return $this->hasMany(FraudCheck::class);
    }

    public function latestFraudCheck()
    {
        return $this->hasOne(FraudCheck::class)->latestOfMany('updated_at');
    }

    public function verificationDocuments()
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function userDeviceLogs()
    {
        return $this->hasMany(UserDeviceLog::class);
    }

    public function reportsAgainst()
    {
        return $this->hasMany(UserReport::class, 'reported_user_id');
    }
}
