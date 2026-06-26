<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_verification_requirements')) {
            Schema::create('b2b_verification_requirements', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->string('slug')->unique();
                $table->string('field_type', 50)->default('text');
                $table->text('help_text')->nullable();
                $table->string('placeholder')->nullable();
                $table->json('company_types')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_company_verification_submissions')) {
            Schema::create('b2b_company_verification_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('b2b_company_id');
                $table->foreign('b2b_company_id', 'b2b_comp_ver_sub_company_fk')
                    ->references('id')
                    ->on('b2b_companies')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('b2b_verification_requirement_id');
                $table->foreign('b2b_verification_requirement_id', 'b2b_comp_ver_sub_req_fk')
                    ->references('id')
                    ->on('b2b_verification_requirements')
                    ->cascadeOnDelete();
                $table->text('value_text')->nullable();
                $table->string('value_file')->nullable();
                $table->timestamps();

                $table->unique(['b2b_company_id', 'b2b_verification_requirement_id'], 'b2b_company_requirement_unique');
            });
        }

        $now = now();
        $requirements = [
            [
                'label' => 'Certificate of Incorporation / Business Registration',
                'slug' => 'certificate-of-incorporation',
                'field_type' => 'file',
                'help_text' => 'Primary company registration certificate or incorporation document.',
                'placeholder' => null,
                'company_types' => null,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'label' => 'Legal Representative / Owner ID',
                'slug' => 'legal-representative-id',
                'field_type' => 'file',
                'help_text' => 'Passport, national ID, or driving license of owner or legal representative.',
                'placeholder' => null,
                'company_types' => null,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'label' => 'Proof of Business Address',
                'slug' => 'proof-of-business-address',
                'field_type' => 'file',
                'help_text' => 'Utility bill, lease agreement, or bank statement showing business address.',
                'placeholder' => null,
                'company_types' => null,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'label' => 'Import / Export License',
                'slug' => 'import-export-license',
                'field_type' => 'file',
                'help_text' => 'Upload exporter, importer, or customs registration if applicable.',
                'placeholder' => null,
                'company_types' => json_encode(['supplier', 'manufacturer', 'distributor', 'wholesaler']),
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'label' => 'Factory Audit / Production Capability Report',
                'slug' => 'factory-audit-report',
                'field_type' => 'file',
                'help_text' => 'Third-party audit, production capacity profile, or factory inspection report.',
                'placeholder' => null,
                'company_types' => json_encode(['supplier', 'manufacturer']),
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'label' => 'Quality / Product Compliance Certificate',
                'slug' => 'quality-compliance-certificate',
                'field_type' => 'file',
                'help_text' => 'ISO, CE, FDA, RoHS, HACCP, GMP, or other relevant compliance file.',
                'placeholder' => null,
                'company_types' => json_encode(['supplier', 'manufacturer', 'distributor', 'wholesaler']),
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'label' => 'Authorized Contact Person',
                'slug' => 'authorized-contact-person',
                'field_type' => 'text',
                'help_text' => 'Name of the person responsible for verification and trade compliance.',
                'placeholder' => 'Full name',
                'company_types' => null,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 70,
            ],
            [
                'label' => 'Authorized Contact Email',
                'slug' => 'authorized-contact-email',
                'field_type' => 'email',
                'help_text' => 'Email used for document verification follow-up.',
                'placeholder' => 'compliance@company.com',
                'company_types' => null,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 80,
            ],
        ];

        foreach ($requirements as $requirement) {
            DB::table('b2b_verification_requirements')->updateOrInsert(
                ['slug' => $requirement['slug']],
                array_merge($requirement, ['updated_at' => $now, 'created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_company_verification_submissions');
        Schema::dropIfExists('b2b_verification_requirements');
    }
};
