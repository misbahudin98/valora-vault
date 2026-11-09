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
        // 1. Master Billing Rates Table
        Schema::create('master_billing_rates', function (Blueprint $table) {
            $table->id();
            $table->string('rate_key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit');
            $table->decimal('price', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Billing Rate Histories Table
        Schema::create('billing_rate_histories', function (Blueprint $table) {
            $table->id();
            $table->string('rate_key');
            $table->string('item_name');
            $table->decimal('old_price', 15, 2)->default(0.00);
            $table->decimal('new_price', 15, 2)->default(0.00);
            $table->text('reason');
            $table->string('updated_by')->default('Admin');
            $table->timestamps();

            $table->index('rate_key');
        });

        // 3. Client Project Payments Table
        Schema::create('client_project_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('client_projects')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('Transfer BCA');
            $table->string('reference_no')->nullable();
            $table->string('recorded_by')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_project_payments');
        Schema::dropIfExists('billing_rate_histories');
        Schema::dropIfExists('master_billing_rates');
    }
};
