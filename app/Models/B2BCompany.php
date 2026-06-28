<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class B2BCompany extends Model
{
    public const SUPPLIER_TYPES = ['supplier', 'manufacturer', 'wholesaler', 'distributor', 'exporter'];
    public const BUYER_TYPES = ['buyer', 'retailer', 'importer'];

    protected $table = 'b2b_companies';

    protected $fillable = [
        'user_id',
        'company_name',
        'public_slug',
        'company_type',
        'legal_name',
        'registration_number',
        'tax_number',
        'country',
        'city',
        'address',
        'website',
        'phone',
        'business_email',
        'description',
        'year_established',
        'employee_count',
        'annual_revenue',
        'main_markets',
        'business_scope',
        'production_capacity',
        'export_percentage',
        'factory_size',
        'factory_location',
        'quality_control',
        'lead_time_summary',
        'response_rate',
        'response_time_hours',
        'profile_score',
        'public_profile_enabled',
        'verified_supplier_badge',
        'premium_verified',
        'featured_supplier',
        'logo',
        'trade_license_file',
        'tax_document_file',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'bank_branch_name',
        'bank_branch_address',
        'bank_country',
        'swift_code',
        'iban',
        'bank_check_file',
        'b2b_package_id',
        'featured_b2b_package_id',
        'product_promotion_package_id',
        'premium_verification_package_id',
        'package_started_at',
        'package_expires_at',
        'featured_package_started_at',
        'featured_package_expires_at',
        'product_promotion_started_at',
        'product_promotion_expires_at',
        'premium_verified_at',
        'verification_status',
        'verification_note',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'public_profile_enabled' => 'boolean',
        'verified_supplier_badge' => 'boolean',
        'premium_verified' => 'boolean',
        'featured_supplier' => 'boolean',
        'verified_at' => 'datetime',
        'package_started_at' => 'datetime',
        'package_expires_at' => 'datetime',
        'featured_package_started_at' => 'datetime',
        'featured_package_expires_at' => 'datetime',
        'product_promotion_started_at' => 'datetime',
        'product_promotion_expires_at' => 'datetime',
        'premium_verified_at' => 'datetime',
        'year_established' => 'integer',
        'response_time_hours' => 'integer',
        'profile_score' => 'integer',
        'export_percentage' => 'decimal:2',
        'response_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rfqs()
    {
        return $this->hasMany(B2BRfq::class, 'b2b_company_id');
    }

    public function supplierQuotations()
    {
        return $this->hasMany(B2BQuotation::class, 'supplier_company_id');
    }

    public function buyerPurchaseOrders()
    {
        return $this->hasMany(B2BPurchaseOrder::class, 'buyer_company_id');
    }

    public function supplierPurchaseOrders()
    {
        return $this->hasMany(B2BPurchaseOrder::class, 'supplier_company_id');
    }

    public function buyerInvoices()
    {
        return $this->hasMany(B2BProformaInvoice::class, 'buyer_company_id');
    }

    public function supplierInvoices()
    {
        return $this->hasMany(B2BProformaInvoice::class, 'supplier_company_id');
    }

    public function buyerNegotiations()
    {
        return $this->hasMany(B2BNegotiation::class, 'buyer_company_id');
    }

    public function supplierNegotiations()
    {
        return $this->hasMany(B2BNegotiation::class, 'supplier_company_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(B2BAuditLog::class, 'actor_company_id');
    }

    public function members()
    {
        return $this->hasMany(B2BCompanyMember::class, 'b2b_company_id');
    }

    public function invitations()
    {
        return $this->hasMany(B2BCompanyInvitation::class, 'b2b_company_id');
    }

    public function certifications()
    {
        return $this->hasMany(B2BCompanyCertification::class, 'b2b_company_id');
    }

    public function verificationSubmissions()
    {
        return $this->hasMany(B2BCompanyVerificationSubmission::class, 'b2b_company_id');
    }

    public function b2bPackage()
    {
        return $this->belongsTo(B2BPackage::class, 'b2b_package_id');
    }

    public function packageRequests()
    {
        return $this->hasMany(B2BPackageRequest::class, 'b2b_company_id');
    }

    public function membershipPackageRequests()
    {
        return $this->packageRequests()->where('request_type', 'membership');
    }

    public function featuredPackageRequests()
    {
        return $this->packageRequests()->where('request_type', 'supplier_featured');
    }

    public function productPromotionPackage()
    {
        return $this->belongsTo(B2BProductPromotionPackage::class, 'product_promotion_package_id');
    }

    public function productPromotionRequests()
    {
        return $this->hasMany(B2BProductPromotionRequest::class, 'b2b_company_id');
    }

    public function premiumVerificationPackage()
    {
        return $this->belongsTo(B2BPremiumVerificationPackage::class, 'premium_verification_package_id');
    }

    public function premiumVerificationRequests()
    {
        return $this->hasMany(B2BPremiumVerificationRequest::class, 'b2b_company_id');
    }

    public function productPromotions()
    {
        return $this->hasMany(B2BProductPromotion::class, 'b2b_company_id');
    }

    public function companyCategories()
    {
        return $this->hasMany(B2BCompanyCategory::class, 'b2b_company_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'b2b_company_categories', 'b2b_company_id', 'category_id')->withTimestamps();
    }

    public function wholesaleProducts()
    {
        return $this->hasMany(Product::class, 'user_id', 'user_id')
            ->where('wholesale_product', 1)
            ->where('approved', 1)
            ->where('published', 1);
    }

    public function targetedRfqs()
    {
        return $this->hasMany(B2BRfq::class, 'supplier_company_id');
    }

    public function buyerSampleOrders()
    {
        return $this->hasMany(B2BSampleOrder::class, 'buyer_company_id');
    }

    public function supplierSampleOrders()
    {
        return $this->hasMany(B2BSampleOrder::class, 'supplier_company_id');
    }

    public function buyerShippingQuotes()
    {
        return $this->hasMany(B2BShippingQuote::class, 'buyer_company_id');
    }

    public function supplierShippingQuotes()
    {
        return $this->hasMany(B2BShippingQuote::class, 'supplier_company_id');
    }

    public function buyerShipments()
    {
        return $this->hasMany(B2BShipment::class, 'buyer_company_id');
    }

    public function supplierShipments()
    {
        return $this->hasMany(B2BShipment::class, 'supplier_company_id');
    }

    public function scopeSupplierSide($query)
    {
        return $query->whereIn('company_type', self::SUPPLIER_TYPES);
    }

    public function scopeApprovedSupplierSide($query)
    {
        return $query->supplierSide()->where('verification_status', 'approved');
    }

    public function scopePublicSuppliers($query)
    {
        return $query->approvedSupplierSide()->where('public_profile_enabled', true);
    }

    public function scopeHomepageFeaturedSuppliers($query)
    {
        return $query->publicSuppliers()
            ->where('featured_supplier', true)
            ->where(function ($query) {
                $query
                    ->where(function ($featuredQuery) {
                        $featuredQuery->whereNotNull('featured_b2b_package_id')
                            ->where(function ($expiryQuery) {
                                $expiryQuery->whereNull('featured_package_expires_at')
                                    ->orWhere('featured_package_expires_at', '>=', now());
                            })
                            ->whereHas('featuredB2bPackage', function ($packageQuery) {
                                $packageQuery->where('package_for', 'supplier')
                                    ->where('package_type', 'supplier_featured')
                                    ->where('featured_profile', true)
                                    ->where('is_active', true);
                            });
                    })
                    ->orWhere(function ($membershipQuery) {
                        $membershipQuery->whereNotNull('b2b_package_id')
                            ->where(function ($expiryQuery) {
                                $expiryQuery->whereNull('package_expires_at')
                                    ->orWhere('package_expires_at', '>=', now());
                            })
                            ->whereHas('b2bPackage', function ($packageQuery) {
                                $packageQuery->where('package_for', 'supplier')
                                    ->where('featured_profile', true)
                                    ->where('is_active', true);
                            });
                    });
            });
    }

    public function featuredB2bPackage()
    {
        return $this->belongsTo(B2BPackage::class, 'featured_b2b_package_id');
    }

    public function isSupplierSide(): bool
    {
        return in_array($this->company_type, self::SUPPLIER_TYPES, true);
    }

    public function isBuyerSide(): bool
    {
        return in_array($this->company_type, self::BUYER_TYPES, true);
    }

    public function hasActiveFeaturedHomepagePlan(): bool
    {
        $hasDedicatedFeaturedPackage = $this->featuredB2bPackage
            && $this->featuredB2bPackage->is_active
            && $this->featuredB2bPackage->package_for === 'supplier'
            && $this->featuredB2bPackage->isSupplierFeaturedPackage()
            && $this->featuredB2bPackage->featured_profile
            && ($this->featured_package_expires_at?->isFuture() !== false);

        $hasFeaturedMembershipPackage = $this->b2bPackage
            && $this->b2bPackage->is_active
            && $this->b2bPackage->package_for === 'supplier'
            && $this->b2bPackage->featured_profile
            && ($this->package_expires_at?->isFuture() !== false);

        return $this->featured_supplier
            && $this->isSupplierSide()
            && $this->public_profile_enabled
            && $this->verification_status === 'approved'
            && ($hasDedicatedFeaturedPackage || $hasFeaturedMembershipPackage);
    }

    public function fraudBadgeState(): string
    {
        $check = $this->user?->latestFraudCheck;

        if (!$check) {
            return 'under_review';
        }

        if ($check->status === 'blocked' || $check->risk_level === 'blocked') {
            return 'blocked_supplier';
        }

        if (in_array($check->risk_level, ['high', 'critical'], true)) {
            return 'high_risk_supplier';
        }

        if ($this->verified_supplier_badge && $this->verification_status === 'approved') {
            return 'verified_supplier';
        }

        return $this->verification_status === 'pending' ? 'document_pending' : 'under_review';
    }

    protected static function booted()
    {
        static::saving(function (B2BCompany $company) {
            if (!$company->company_name) {
                return;
            }

            if (!$company->public_slug || $company->isDirty('company_name')) {
                $company->public_slug = static::generateUniquePublicSlug($company->company_name, $company->id);
            }
        });

        static::created(function (B2BCompany $company) {
            if (!Schema::hasTable('b2b_company_members')) {
                return;
            }

            B2BCompanyMember::firstOrCreate(
                [
                    'b2b_company_id' => $company->id,
                    'user_id' => $company->user_id,
                ],
                [
                    'role' => 'owner',
                    'status' => 'active',
                    'joined_at' => now(),
                ]
            );
        });
    }

    protected static function generateUniquePublicSlug(string $companyName, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($companyName);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'supplier';
        $slug = $baseSlug;
        $counter = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('public_slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
