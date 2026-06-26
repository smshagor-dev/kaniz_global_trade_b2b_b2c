<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createElementsTable();
        $this->createElementTypesTable();
        $this->createElementStylesTable();
        $this->createTicketsTable();
        $this->ensureLegacyOrderViewColumns();
        $this->seedElementDefaults();
        $this->seedElementSettings();
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }

    protected function createElementsTable(): void
    {
        if (Schema::hasTable('elements')) {
            return;
        }

        Schema::create('elements', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function createElementTypesTable(): void
    {
        if (Schema::hasTable('element_types')) {
            return;
        }

        Schema::create('element_types', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('element_id');
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    protected function createElementStylesTable(): void
    {
        if (Schema::hasTable('element_styles')) {
            return;
        }

        Schema::create('element_styles', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('element_type_id');
            $table->string('name', 100);
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    protected function createTicketsTable(): void
    {
        if (Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedBigInteger('code')->default(0);
            $table->integer('user_id')->default(0);
            $table->string('subject')->default('Support Request');
            $table->longText('details')->nullable();
            $table->longText('files')->nullable();
            $table->string('status', 10)->default('pending');
            $table->integer('viewed')->default(0);
            $table->integer('client_viewed')->default(0);
            $table->timestamps();
        });
    }

    protected function ensureLegacyOrderViewColumns(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_viewed')) {
                $table->integer('delivery_viewed')->default(1);
            }

            if (!Schema::hasColumn('orders', 'payment_status_viewed')) {
                $table->integer('payment_status_viewed')->default(1);
            }
        });
    }

    protected function seedElementDefaults(): void
    {
        if (Schema::hasTable('elements')) {
            foreach ([
                ['id' => 1, 'name' => 'Header'],
                ['id' => 2, 'name' => 'Footer'],
                ['id' => 3, 'name' => 'Megamenu'],
            ] as $element) {
                DB::table('elements')->updateOrInsert(
                    ['id' => $element['id']],
                    array_merge($element, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        }

        if (Schema::hasTable('element_types')) {
            foreach ([
                ['id' => 1, 'element_id' => 1, 'name' => 'Header 1'],
                ['id' => 2, 'element_id' => 1, 'name' => 'Header 2'],
                ['id' => 3, 'element_id' => 1, 'name' => 'Header 3'],
                ['id' => 4, 'element_id' => 1, 'name' => 'Header 4'],
                ['id' => 5, 'element_id' => 1, 'name' => 'Header 5'],
                ['id' => 6, 'element_id' => 1, 'name' => 'Header 6'],
                ['id' => 7, 'element_id' => 2, 'name' => 'Footer 1'],
                ['id' => 8, 'element_id' => 2, 'name' => 'Footer 2'],
                ['id' => 9, 'element_id' => 2, 'name' => 'Footer 3'],
                ['id' => 10, 'element_id' => 1, 'name' => 'Header 7'],
                ['id' => 11, 'element_id' => 3, 'name' => 'Megamenu 1'],
                ['id' => 12, 'element_id' => 3, 'name' => 'Megamenu 2'],
            ] as $type) {
                DB::table('element_types')->updateOrInsert(
                    ['id' => $type['id']],
                    array_merge($type, ['is_default' => 0, 'created_at' => now(), 'updated_at' => now()])
                );
            }
        }

        if (Schema::hasTable('element_styles')) {
            foreach ([
                ['id' => 1, 'element_type_id' => 1, 'name' => 'top_header_bg_color', 'value' => '#ffffff'],
                ['id' => 2, 'element_type_id' => 1, 'name' => 'middle_header_bg_color', 'value' => '#ffffff'],
                ['id' => 3, 'element_type_id' => 1, 'name' => 'bottom_header_bg_color', 'value' => '#ff0000'],
                ['id' => 4, 'element_type_id' => 1, 'name' => 'top_header_text_color', 'value' => '#857E7E'],
                ['id' => 5, 'element_type_id' => 1, 'name' => 'middle_header_text_color', 'value' => '#857E7E'],
                ['id' => 6, 'element_type_id' => 1, 'name' => 'bottom_header_text_color', 'value' => '#ffffff'],
                ['id' => 33, 'element_type_id' => 7, 'name' => 'footer_bg_color', 'value' => '#000000'],
                ['id' => 35, 'element_type_id' => 7, 'name' => 'footer_text_color', 'value' => '#ffffff'],
            ] as $style) {
                DB::table('element_styles')->updateOrInsert(
                    ['id' => $style['id']],
                    array_merge($style, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        }
    }

    protected function seedElementSettings(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        foreach ([
            'header_element' => '1',
            'footer_element' => '7',
        ] as $type => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $type, 'lang' => null],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
};
