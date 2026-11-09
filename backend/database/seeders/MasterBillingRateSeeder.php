<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BillingRateHistory;
use App\Models\MasterBillingRate;
use Illuminate\Database\Seeder;

class MasterBillingRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Hapus template lama dan tarif rincian kapabilitas agar bersih dan rapi
        MasterBillingRate::whereIn('rate_key', [
            'modul_crud_master',
            'modul_analytics_dashboard',
            'modul_transaction_engine',
            'modul_reporting_export',
        ])->orWhere('rate_key', 'like', 'cap_%')->delete();

        // 2. Daftar 6 Tarif Standar Sistem & Komponen Inti
        $rates = [
            [
                'rate_key' => 'landing',
                'name' => 'Landing Page Responsive',
                'description' => 'Desain web company profile beranimasi, kuartet SEO/AEO/GEO & mobile-first',
                'unit' => 'Per Proyek',
                'category' => 'system_core',
                'price' => 2500000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'login',
                'name' => 'Halaman Login Otentikasi',
                'description' => 'Form keamanan login, proteksi brute-force, CSRF & session token Sanctum',
                'unit' => 'Per Halaman',
                'category' => 'system_core',
                'price' => 1000000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'register',
                'name' => 'Halaman Register Mandiri',
                'description' => 'Pendaftaran akun, verifikasi email, password hashing Argon2id & aktivasi user',
                'unit' => 'Per Halaman',
                'category' => 'system_core',
                'price' => 1000000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'menu_price',
                'name' => 'Menu Aplikasi / Modul Kerja',
                'description' => 'Tarif acuan pengerjaan menu modul operasional mencakup routing, permission & kerangka kerja',
                'unit' => 'Per Menu Modul',
                'category' => 'system_core',
                'price' => 750000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'database_table_price',
                'name' => 'Tabel Database Relasional',
                'description' => 'Skema tabel 3NF, relasi foreign key, indeks performa & migrasi transaksional',
                'unit' => 'Per Tabel DB',
                'category' => 'system_core',
                'price' => 100000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'maintenance_menu_price',
                'name' => 'Biaya Maintenance per Menu Modul',
                'description' => 'Tarif pengerjaan pemeliharaan berkala, patch bug-fix, & update operasional',
                'unit' => 'Per Menu Modul',
                'category' => 'system_core',
                'price' => 150000.00,
                'is_active' => true,
            ],
        ];

        foreach ($rates as $rate) {
            MasterBillingRate::updateOrCreate(
                ['rate_key' => $rate['rate_key']],
                $rate
            );
        }

        $histories = [
            [
                'rate_key' => 'database_table_price',
                'item_name' => 'Tabel Database Relasional',
                'old_price' => 75000.00,
                'new_price' => 100000.00,
                'reason' => 'Standarisasi arsitektur database relasional PostgreSQL & SQLite',
                'updated_by' => 'Admin Valora',
                'created_at' => '2026-01-01 10:00:00',
            ],
            [
                'rate_key' => 'menu_price',
                'item_name' => 'Menu Aplikasi / Modul Kerja',
                'old_price' => 600000.00,
                'new_price' => 750000.00,
                'reason' => 'Penyesuaian kompleksitas modul API dan logic business',
                'updated_by' => 'Admin Valora',
                'created_at' => '2026-01-15 14:30:00',
            ],
            [
                'rate_key' => 'landing',
                'item_name' => 'Landing Page Responsive',
                'old_price' => 2000000.00,
                'new_price' => 2500000.00,
                'reason' => 'Peningkatan UI/UX interaktif dan optimasi SEO Tailwind',
                'updated_by' => 'Admin Valora',
                'created_at' => '2026-01-20 09:15:00',
            ],
        ];

        foreach ($histories as $hist) {
            BillingRateHistory::firstOrCreate(
                [
                    'rate_key' => $hist['rate_key'],
                    'new_price' => $hist['new_price'],
                    'reason' => $hist['reason'],
                ],
                $hist
            );
        }
    }
}
