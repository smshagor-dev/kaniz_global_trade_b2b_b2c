<?php

namespace Tests\Feature\B2B;

use Symfony\Component\HttpKernel\Exception\HttpException;

class B2BPermissionTest extends B2BFeatureTestCase
{
    public function test_viewer_cannot_create_rfq(): void
    {
        $owner = $this->createUser();
        $company = $this->createCompany($owner, ['company_type' => 'buyer']);
        $viewer = $this->createUser();
        $this->createCompanyMember($company, $viewer, 'viewer');
        $this->setActiveCompany($company);

        $response = $this->actingAs($viewer)->post(route('b2b.rfqs.store'), [
            'title' => 'Viewer RFQ',
            'description' => 'Should not be allowed',
            'quantity' => 10,
            'currency' => 'USD',
        ]);

        $response->assertRedirect(route('b2b.company.show'));
        $this->assertDatabaseMissing('b2b_rfqs', [
            'title' => 'Viewer RFQ',
        ]);
    }

    public function test_viewer_cannot_manage_supplier_public_profile(): void
    {
        $owner = $this->createSellerUser();
        $company = $this->createCompany($owner, ['company_type' => 'supplier']);
        $viewer = $this->createSellerUser();
        $this->createCompanyMember($company, $viewer, 'viewer');
        $this->setActiveCompany($company);

        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        try {
            $this->actingAs($viewer)->get(route('seller.b2b.company.public-profile'));
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            throw $exception;
        }
    }

    public function test_sales_manager_can_manage_supplier_public_profile(): void
    {
        $owner = $this->createSellerUser();
        $company = $this->createCompany($owner, ['company_type' => 'supplier']);
        $salesManager = $this->createSellerUser();
        $this->createCompanyMember($company, $salesManager, 'sales_manager');
        $category = $this->createCategory();
        $this->setActiveCompany($company);

        $this->actingAs($salesManager)->post(route('seller.b2b.company.public-profile.update'), [
            'year_established' => 2010,
            'business_scope' => 'OEM manufacturing',
            'public_profile_enabled' => 1,
            'category_ids' => [$category->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('b2b_companies', [
            'id' => $company->id,
            'year_established' => 2010,
            'business_scope' => 'OEM manufacturing',
            'public_profile_enabled' => 1,
        ]);
        $this->assertDatabaseHas('b2b_company_categories', [
            'b2b_company_id' => $company->id,
            'category_id' => $category->id,
        ]);
    }
}
