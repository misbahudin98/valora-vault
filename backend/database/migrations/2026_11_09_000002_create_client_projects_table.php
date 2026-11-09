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
        Schema::create('client_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('project_name')->index();
            $table->string('app_name')->index();
            $table->string('project_type')->default('development')->index();
            $table->string('lead_pic')->nullable();
            $table->string('github_url')->nullable();
            $table->text('app_desc')->nullable();
            $table->boolean('use_landing')->default(false);
            $table->boolean('use_login')->default(true);
            $table->boolean('use_register')->default(false);
            $table->boolean('use_app_core')->default(true);
            $table->decimal('landing_price', 15, 2)->default(0);
            $table->decimal('login_price', 15, 2)->default(0);
            $table->decimal('register_price', 15, 2)->default(0);
            $table->string('landing_note')->nullable();
            $table->string('login_note')->nullable();
            $table->string('register_note')->nullable();
            $table->string('status')->default('Aktif')->index();
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_projects');
    }
};
