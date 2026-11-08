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

        $appUrl = env('APP_URL', 'http://valora-vault.test');
        $domainHost = parse_url($appUrl, PHP_URL_HOST) ?: 'valora-vault.test';
        $appName = env('APP_NAME', 'Manajemen Proyek');

        $adminEmail = "admin@{$domainHost}";

        // Bersihkan seluruh akun selain akun admin utama aplikasi
        User::where('email', '!=', $adminEmail)->delete();

        // Buat atau perbarui akun Administrator Utama dengan peran mutlak ['admin']
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => "{$appName} Admin",
                'password' => 'password123',
                'role' => ['admin'],
                'is_active' => true,
                'token_version' => 1,
            ]
        );
    }
}
