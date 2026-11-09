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
        // 1. Tambah field profil staf & hak akses fitur pada tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->string('jabatan')->nullable()->after('role');
            $table->string('phone', 50)->nullable()->after('jabatan');
            $table->text('address')->nullable()->after('phone');
            $table->string('github_url')->nullable()->after('address');
            $table->string('linkedin_url')->nullable()->after('github_url');
            $table->json('skills')->nullable()->after('linkedin_url');
            $table->json('allowed_features')->nullable()->after('skills');
        });

        // 2. Hubungkan lead_pic_id pada client_projects ke users
        Schema::table('client_projects', function (Blueprint $table) {
            $table->foreignId('lead_pic_id')->nullable()->after('lead_pic')->constrained('users')->nullOnDelete();
        });

        // 3. Buat pivot table pekerja proyek (assigned workers)
        Schema::create('client_project_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('client_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_project')->default('Pekerja');
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // 4. Hubungkan pic_user_id pada project_menus ke users
        Schema::table('project_menus', function (Blueprint $table) {
            $table->foreignId('pic_user_id')->nullable()->after('pic')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_menus', function (Blueprint $table) {
            $table->dropForeign(['pic_user_id']);
            $table->dropColumn('pic_user_id');
        });

        Schema::dropIfExists('client_project_workers');

        Schema::table('client_projects', function (Blueprint $table) {
            $table->dropForeign(['lead_pic_id']);
            $table->dropColumn('lead_pic_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'jabatan',
                'phone',
                'address',
                'github_url',
                'linkedin_url',
                'skills',
                'allowed_features',
            ]);
        });
    }
};
