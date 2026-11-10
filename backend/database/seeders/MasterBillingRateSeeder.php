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

        // 2. Daftar 6 Tarif Standar Sistem & Komponen Inti (Terjangkau, Profesional & Bernilai Tinggi)
        $rates = [
            [
                'rate_key' => 'landing',
                'name' => 'Landing Page Responsive',
                'description' => 'Desain web company profile beranimasi, kuartet SEO/AEO/GEO & mobile-first',
                'unit' => 'Per Proyek',
                'category' => 'system_core',
                'price' => 500000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'login',
                'name' => 'Halaman Login Otentikasi',
                'description' => 'Form keamanan login, proteksi brute-force, CSRF & session token Sanctum',
                'unit' => 'Per Halaman',
                'category' => 'system_core',
                'price' => 350000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'register',
                'name' => 'Halaman Register Mandiri',
                'description' => 'Pendaftaran akun, verifikasi email, password hashing Argon2id & aktivasi user',
                'unit' => 'Per Halaman',
                'category' => 'system_core',
                'price' => 350000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'menu_price',
                'name' => 'Menu Aplikasi / Modul Kerja',
                'description' => 'Tarif acuan pengerjaan menu modul operasional mencakup routing, permission & kerangka kerja',
                'unit' => 'Per Menu Modul',
                'category' => 'system_core',
                'price' => 250000.00,
                'is_active' => true,
            ],
            [
                'rate_key' => 'database_table_price',
                'name' => 'Tabel Database Relasional',
                'description' => 'Skema tabel 3NF, relasi foreign key, indeks performa & migrasi transaksional',
                'unit' => 'Per Tabel DB',
                'category' => 'system_core',
                'price' => 150000.00,
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

        // 3. Bersihkan riwayat tarif (ditetapkan sebagai harga awal bersih tanpa riwayat)
        BillingRateHistory::query()->delete();
    }
}
