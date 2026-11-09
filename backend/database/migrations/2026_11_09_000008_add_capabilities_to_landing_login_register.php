<?php

declare(strict_types=1);

use App\Models\MasterBillingRate;
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
        // 1. Tambahkan kolom capabilities untuk landing, login, dan register pada client_projects
        Schema::table('client_projects', function (Blueprint $table) {
            $table->json('landing_capabilities')->nullable()->after('landing_note');
            $table->json('login_capabilities')->nullable()->after('login_note');
            $table->json('register_capabilities')->nullable()->after('register_note');
        });

        // 2. Bersihkan tarif kapabilitas fitur dari master_billing_rates (hanya tersisa 6 tarif standar inti)
        MasterBillingRate::where('rate_key', 'like', 'cap_%')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_projects', function (Blueprint $table) {
            $table->dropColumn([
                'landing_capabilities',
                'login_capabilities',
                'register_capabilities',
            ]);
        });
    }
};
