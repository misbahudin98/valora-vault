<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database (Strict Admin-Only Architecture).
     */
    public function run(): void
    {
        UserDevice::query()->delete();

        $appUrl = (string) config('app.url', 'http://valora-vault.test');
        $domainHost = parse_url($appUrl, PHP_URL_HOST) ?: 'valora-vault.test';
        $appName = (string) config('app.name', 'Manajemen Proyek');

        $adminEmail = "admin@{$domainHost}";

        $staffEmails = [
            $adminEmail,
            "iqbal@{$domainHost}",
            "misbah@{$domainHost}",
        ];

        // Bersihkan seluruh akun selain akun staf inti aplikasi
        User::whereNotIn('email', $staffEmails)->delete();

        // 1. Akun Administrator Utama
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => "{$appName} Admin",
                'password' => 'password123',
                'role' => ['admin'],
                'is_active' => true,
                'token_version' => 1,
                'jabatan' => 'Agency Principal & Lead Architect',
                'phone' => '0812-3456-7890',
                'address' => 'Cyber Tower Lt. 18, Jl. Rasuna Said, Jakarta Selatan',
                'github_url' => 'https://github.com/valora-admin',
                'linkedin_url' => 'https://linkedin.com/in/valora-admin',
                'skills' => ['System Architecture', 'Security Hardening', 'Database Modeling', 'Laravel 13', 'Svelte 5'],
                'allowed_features' => ['*'],
            ]
        );

        // 2. Akun Lead Fullstack Engineer: Muhammad Iqbal Murtadho
        User::updateOrCreate(
            ['email' => "iqbal@{$domainHost}"],
            [
                'name' => 'Muhammad Iqbal Murtadho',
                'password' => 'password123',
                'role' => ['admin'],
                'is_active' => true,
                'token_version' => 1,
                'jabatan' => 'Lead Fullstack Engineer',
                'phone' => '0813-8899-1122',
                'address' => 'Jl. Darmo Permai III No. 12, Surabaya',
                'github_url' => 'https://github.com/iqbalmurtadho',
                'linkedin_url' => 'https://linkedin.com/in/iqbalmurtadho',
                'skills' => ['Laravel', 'Svelte 5', 'Tailwind CSS', 'PostgreSQL', 'REST API'],
                'allowed_features' => ['dashboard', 'admin-clients', 'admin-progres', 'admin-tagihan'],
            ]
        );

        // 3. Akun Senior Backend Architect: Misbahudin
        User::updateOrCreate(
            ['email' => "misbah@{$domainHost}"],
            [
                'name' => 'Misbahudin',
                'password' => 'password123',
                'role' => ['admin'],
                'is_active' => true,
                'token_version' => 1,
                'jabatan' => 'Senior Backend & Database Architect',
                'phone' => '0819-7766-5544',
                'address' => 'Jl. Fatmawati Raya No. 88, Jakarta Selatan',
                'github_url' => 'https://github.com/misbahudin',
                'linkedin_url' => 'https://linkedin.com/in/misbahudin',
                'skills' => ['PHP 8.4', 'Database Architecture', 'OWASP ASVS', 'Docker', 'Linux Server'],
                'allowed_features' => ['dashboard', 'admin-clients', 'admin-progres', 'admin-tagihan', 'admin-system'],
            ]
        );

        // 4. Inisialisasi Profil Legal Agensi PT Valora Digital Solusindo (SSoT)
        \App\Models\AgencyProfile::getProfile();

        // 5. Inisialisasi Master Tarif Baku Agensi
        $this->call(MasterBillingRateSeeder::class);
    }
}
