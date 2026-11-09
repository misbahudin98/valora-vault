<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_billing_rates', function (Blueprint $table) {
            $table->string('category')->default('system_core')->after('unit');
            $table->boolean('has_create')->default(true)->after('price');
            $table->boolean('has_read')->default(true)->after('has_create');
            $table->boolean('has_update')->default(true)->after('has_read');
            $table->boolean('has_delete')->default(true)->after('has_update');
            $table->boolean('has_grafik')->default(false)->after('has_delete');
            $table->boolean('has_box_dashboard')->default(false)->after('has_grafik');
            $table->boolean('has_import')->default(false)->after('has_box_dashboard');
            $table->boolean('has_export')->default(false)->after('has_import');
            $table->boolean('has_filter')->default(true)->after('has_export');
            $table->boolean('has_print')->default(false)->after('has_filter');
            $table->boolean('has_notification')->default(false)->after('has_print');
            $table->boolean('has_custom_logic')->default(false)->after('has_notification');
        });

        Schema::table('project_menus', function (Blueprint $table) {
            $table->string('module_type')->nullable()->after('menu_desc');
            $table->boolean('has_notification')->default(false)->after('has_print');
            $table->boolean('has_custom_logic')->default(false)->after('has_notification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_menus', function (Blueprint $table) {
            $table->dropColumn(['module_type', 'has_notification', 'has_custom_logic']);
        });

        Schema::table('master_billing_rates', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'has_create',
                'has_read',
                'has_update',
                'has_delete',
                'has_grafik',
                'has_box_dashboard',
                'has_import',
                'has_export',
                'has_filter',
                'has_print',
                'has_notification',
                'has_custom_logic',
            ]);
        });
    }
};
