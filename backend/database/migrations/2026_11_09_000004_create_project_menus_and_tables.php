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
        Schema::create('project_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_role_id')->constrained('project_roles')->cascadeOnDelete();
            $table->string('menu_name');
            $table->text('menu_desc')->nullable();
            $table->string('pic')->nullable()->index();
            $table->decimal('price', 15, 2)->default(750000);
            $table->string('price_note')->nullable();
            $table->boolean('is_done')->default(false)->index();
            $table->date('completed_date')->nullable();

            // 10 Granular Feature Capabilities
            $table->boolean('has_create')->default(true);
            $table->boolean('has_read')->default(true);
            $table->boolean('has_update')->default(true);
            $table->boolean('has_delete')->default(true);
            $table->boolean('has_grafik')->default(false);
            $table->boolean('has_box_dashboard')->default(false);
            $table->boolean('has_import')->default(false);
            $table->boolean('has_export')->default(false);
            $table->boolean('has_filter')->default(true);
            $table->boolean('has_print')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_role_id', 'is_done']);
            $table->index(['created_at']);
        });

        Schema::create('project_database_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('client_projects')->cascadeOnDelete();
            $table->string('table_name');
            $table->decimal('price', 15, 2)->default(100000);
            $table->boolean('is_done')->default(false)->index();
            $table->timestamps();

            $table->index(['project_id', 'is_done']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_database_tables');
        Schema::dropIfExists('project_menus');
    }
};
