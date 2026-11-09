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
        Schema::create('agency_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('agency_name')->default('PT Valora Digital Solusindo');
            $table->string('legal_name')->default('PT Valora Digital Solusindo');
            $table->string('tagline')->nullable()->default('Software Engineering & Enterprise Web Solutions');
            $table->string('email')->default('billing@valora.id');
            $table->string('hotline_wa')->default('+62 812-9988-7766');
            $table->text('operational_address')->default('Gedung Cyber One, Lantai 5, Jl. H.R. Rasuna Said Kav. 8, Jakarta Selatan 12950');
            $table->string('github_url')->nullable()->default('https://github.com/valora-digital');
            $table->string('website_url')->nullable()->default('https://valora.id');
            $table->string('logo_url')->nullable();
            $table->string('tax_id')->nullable()->default('01.456.789.0-012.000');
            $table->string('bank_name')->default('Bank Central Asia (BCA)');
            $table->string('bank_account_no')->default('827-091-8888');
            $table->string('bank_account_name')->default('PT Valora Digital Solusindo');
            $table->string('secondary_bank_name')->nullable()->default('Bank Mandiri');
            $table->string('secondary_bank_account_no')->nullable()->default('132-00-998877-6');
            $table->string('secondary_bank_account_name')->nullable()->default('PT Valora Digital Solusindo');
            $table->string('signatory_name')->default('Muhammad Iqbal Murtadho');
            $table->string('signatory_title')->default('Managing Director & Lead Architect');
            $table->text('terms_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_profiles');
    }
};
