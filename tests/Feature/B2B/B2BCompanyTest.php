<?php

namespace Tests\Feature\B2B;

use App\Services\B2BCompanyService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class B2BCompanyTest extends B2BFeatureTestCase
{
    public function test_admin_can_approve_and_reject_company_verification(): void
    {
        $admin = $this->createAdminUser();
        $owner = $this->createUser();
        $company = $this->createCompany($owner, [
            'verification_status' => 'pending',
            'company_type' => 'supplier',
        ]);

        $this->actingAs($admin)->post(route('admin.b2b.companies.approve', $company->id))
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_companies', [
            'id' => $company->id,
            'verification_status' => 'approved',
            'verified_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('admin.b2b.companies.reject', $company->id), [
            'verification_note' => 'Missing trade license',
        ])->assertRedirect();

        $this->assertDatabaseHas('b2b_companies', [
            'id' => $company->id,
            'verification_status' => 'rejected',
            'verification_note' => 'Missing trade license',
            'verified_by' => $admin->id,
        ]);
    }

    public function test_active_company_falls_back_when_session_company_is_no_longer_accessible(): void
    {
        $user = $this->createUser();
        $ownedCompany = $this->createCompany($user, ['company_name' => 'Owned Buyer']);

        $supplierOwner = $this->createSellerUser();
        $memberCompany = $this->createCompany($supplierOwner, [
            'company_name' => 'Member Supplier',
            'company_type' => 'supplier',
        ]);

        $this->createCompanyMember($memberCompany, $user, 'admin', 'suspended');
        $this->setActiveCompany($memberCompany);

        $resolvedCompany = app(B2BCompanyService::class)->getCompanyByUser($user->id);

        $this->assertNotNull($resolvedCompany);
        $this->assertSame($ownedCompany->id, $resolvedCompany->id);
        $this->assertSame($ownedCompany->id, session('active_b2b_company_id'));
    }

    public function test_user_cannot_switch_to_company_with_inactive_membership(): void
    {
        $user = $this->createUser();
        $activeCompany = $this->createCompany($user, ['company_name' => 'Primary Buyer']);

        $otherOwner = $this->createSellerUser();
        $inactiveCompany = $this->createCompany($otherOwner, [
            'company_name' => 'Suspended Membership Company',
            'company_type' => 'supplier',
        ]);

        $this->createCompanyMember($inactiveCompany, $user, 'admin', 'suspended');
        $this->setActiveCompany($activeCompany);

        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        try {
            $this->actingAs($user)->post(route('b2b.company.switch'), [
                'company_id' => $inactiveCompany->id,
            ]);
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            throw $exception;
        }

        $this->assertSame($activeCompany->id, session('active_b2b_company_id'));
    }
}
