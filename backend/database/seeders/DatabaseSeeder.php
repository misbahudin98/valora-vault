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
     * Seed the application's database.
     */
    public function run(): void
    {
        UserDevice::query()->delete();
        User::whereNotIn('email', [
            'admin@hyperion-vault.test',
            'user@hyperion-vault.test',
            'admin@template.org',
            'user@template.org'
        ])->delete();

        // Primary Accounts (Hyperion Vault)
        User::updateOrCreate(
            ['email' => 'admin@hyperion-vault.test'],
            [
                'name' => 'Hyperion Admin',
                'password' => 'password123',
                'role' => ['admin', 'manager'],
                'is_active' => true,
                'token_version' => 1,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@hyperion-vault.test'],
            [
                'name' => 'Pengguna Regular',
                'password' => 'password123',
                'role' => ['user'],
                'is_active' => true,
                'token_version' => 1,
            ]
        );

        // Backward-Compatible Fallback Accounts
        User::updateOrCreate(
            ['email' => 'admin@template.org'],
            [
                'name' => 'Admin Legacy',
                'password' => 'password123',
                'role' => ['admin', 'manager'],
                'is_active' => true,
                'token_version' => 1,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@template.org'],
            [
                'name' => 'User Legacy',
                'password' => 'password123',
                'role' => ['user'],
                'is_active' => true,
                'token_version' => 1,
            ]
        );

        // Dynamic Domain Seed Accounts (if custom domain configured in APP_URL)
        $appUrl = env('APP_URL', 'http://hyperion-vault.test');
        $domainHost = parse_url($appUrl, PHP_URL_HOST) ?: 'hyperion-vault.test';
        $appName = env('APP_NAME', 'Administrator');

        if (!in_array($domainHost, ['hyperion-vault.test', 'template.org', 'localhost', '127.0.0.1'], true)) {
            User::updateOrCreate(
                ['email' => "admin@{$domainHost}"],
                [
                    'name' => "{$appName} Admin",
                    'password' => 'password123',
                    'role' => ['admin', 'manager'],
                    'is_active' => true,
                    'token_version' => 1,
                ]
            );

            User::updateOrCreate(
                ['email' => "user@{$domainHost}"],
                [
                    'name' => 'Pengguna Regular',
                    'password' => 'password123',
                    'role' => ['user'],
                    'is_active' => true,
                    'token_version' => 1,
                ]
            );
        }
    }
}
