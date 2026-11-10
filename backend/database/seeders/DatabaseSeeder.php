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

        // 6. Inisialisasi Project Real Notaris & PPAT (Semua Pembiayaan Mengikuti Master Tarif)
        $this->seedNotarisProject();
    }

    /**
     * Inisialisasi Proyek SIP Notaris & PPAT Irwan Baddu secara resmi dan permanen.
     * Seluruh tarif pembiayaan (landing, login, register, menu modul, tabel database)
     * WAJIB secara dinamis mengikuti tarif acuan baku yang ada di Master Tarif.
     */
    private function seedNotarisProject(): void
    {
        $client = \App\Models\Client::firstOrCreate(
            ['name' => 'Kantor Notaris & PPAT Irwan Baddu, S.H., M.Kn.'],
            [
                'contact_person' => 'Irwan Baddu, S.H., M.Kn.',
                'phone' => '0823-3759-5377',
                'email' => 'notaris.irwanbaddu@gmail.com',
                'address' => 'Jl. Pengayoman No. 15, Makassar, Sulawesi Selatan',
                'industry' => 'Jasa Hukum, Kenotariatan & PPAT',
            ]
        );

        $landingPrice = \App\Models\MasterBillingRate::getRatePrice('landing', 500000.0);
        $loginPrice = \App\Models\MasterBillingRate::getRatePrice('login', 350000.0);
        $registerPrice = \App\Models\MasterBillingRate::getRatePrice('register', 350000.0);
        $menuPrice = \App\Models\MasterBillingRate::getRatePrice('menu_price', 250000.0);
        $tablePrice = \App\Models\MasterBillingRate::getRatePrice('database_table_price', 150000.0);

        $project = \App\Models\ClientProject::firstOrCreate(
            ['client_id' => $client->id, 'app_name' => 'SIP Notaris & PPAT Irwan Baddu'],
            [
                'project_name' => 'SIP Notaris & PPAT Irwan Baddu',
                'project_type' => 'development',
                'lead_pic' => 'Muhammad Iqbal Murtadho',
                'lead_pic_id' => 2,
                'github_url' => 'https://github.com/valora-agency/notaris-irwan-baddu',
                'app_desc' => 'Sistem Informasi Penatausahaan Akta, Berkas Perkara, Warkah Pertanahan PPAT, Buku Kas Umum & Monitoring SLA.',
                'use_landing' => true,
                'use_login' => true,
                'use_register' => true,
                'use_app_core' => true,
                'landing_price' => $landingPrice,
                'login_price' => $loginPrice,
                'register_price' => $registerPrice,
                'status' => 'Aktif',
                'grand_total' => 0.0,
                'paid_amount' => 0.0,
            ]
        );

        // Pastikan tarif paket inti selalu sinkron dengan Master Tarif
        $project->update([
            'landing_price' => $landingPrice,
            'login_price' => $loginPrice,
            'register_price' => $registerPrice,
        ]);

        // 6 Skema Tabel Database Relasional (@ mengikuti Master Tarif)
        $tables = [
            'users' => 'Manajemen otentikasi, sandi & hak akses 5 peran berjenjang',
            'berkas' => 'Penatausahaan warkah berkas notaris & PPAT, milestones SLA, upload syarat',
            'keuangan' => 'Pencatatan kas masuk/keluar, buku kas umum, invoice & titipan PNBP',
            'layanan_master' => 'Katalog produk hukum notaris & PPAT beserta standar tarif & syarat warkah',
            'tugas_harian' => 'Disposisi penugasan staf dan log catatan kerja operasional harian',
            'metadata' => 'Identitas profil kantor notaris, legalitas wilayah kerja, dan korespondensi',
        ];

        foreach ($tables as $tbl => $desc) {
            \App\Models\ProjectDatabaseTable::updateOrCreate(
                ['project_id' => $project->id, 'table_name' => $tbl],
                [
                    'price' => $tablePrice,
                    'is_done' => true,
                ]
            );
        }

        // 5 Role Utama
        $rolesList = [
            'Admin Notaris',
            'Admin PPAT',
            'Admin Keuangan',
            'Staf Notaris',
            'Staf PPAT',
        ];

        $roleEntities = [];
        $order = 0;
        foreach ($rolesList as $roleName) {
            $roleEntities[$roleName] = \App\Models\ProjectRole::firstOrCreate(
                ['project_id' => $project->id, 'role_name' => $roleName],
                ['sort_order' => $order++]
            );
        }

        // 27 Menu Modul dari 5 Berkas HTML Notaris (@ mengikuti Master Tarif)
        $menuMapping = [
            'Admin Notaris' => [
                [
                    'menu_name' => 'Dashboard Utama',
                    'menu_desc' => 'Ringkasan berkas perkara notariat, penugasan staf, grafik rasio, dan SLA',
                    'has_create' => false, 'has_read' => true, 'has_update' => false, 'has_delete' => false,
                    'has_grafik' => true, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Buku Register & Berkas Klien',
                    'menu_desc' => 'Pendaftaran permohonan akta, warkah klien, nomor registrasi buku repertorium, dan SK SABU AHU',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => true,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => true, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Daftar Kerja Harian Staf',
                    'menu_desc' => 'Disposisi to-do harian, monitoring tahapan checklist akta, dan verifikasi draf minuta',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Keuangan & Titipan Pajak',
                    'menu_desc' => 'Monitoring status billing berkas, piutang biaya akta, dan titipan PNBP',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => true, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Master Layanan & Biaya',
                    'menu_desc' => 'Katalog produk hukum notaris, komponen biaya standar, dan checklist syarat dokumen',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => true,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Manajemen Data Staf',
                    'menu_desc' => 'Pengelolaan akun tim staf pelaksana notaris, penugasan beban kerja, dan hak akses',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => true,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
            ],
            'Admin PPAT' => [
                [
                    'menu_name' => 'Dashboard PPAT',
                    'menu_desc' => 'Monitoring akta peralihan hak tanah, status loket BPN, dan tren perkara PPAT',
                    'has_create' => false, 'has_read' => true, 'has_update' => false, 'has_delete' => false,
                    'has_grafik' => true, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Buku Register Akta PPAT',
                    'menu_desc' => 'Pencatatan akta AJB, APHB, APHT, Hibah, Tukar Menukar, dan legalisasi warkah tanah',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => true,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => true, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Monitoring Validasi Pajak (BPHTB & PPh)',
                    'menu_desc' => 'Tracking pembayaran e-BPHTB Bapenda dan validasi SSP PPh KPP Pratama',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => true, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Tracking Loket Kantor Pertanahan (BPN)',
                    'menu_desc' => 'Integrasi monitoring status berkas loket BPN (SK Hak, Balik Nama, HT Elektronik, Roya)',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Laporan Bulanan PPAT ke BPN & Bapenda',
                    'menu_desc' => 'Penyusunan dan ekspor rekapitulasi laporan akta bulanan wajib PPAT format standar BPN',
                    'has_create' => true, 'has_read' => true, 'has_update' => false, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Katalog Layanan PPAT & Persyaratan',
                    'menu_desc' => 'Daftar master jasa peralihan tanah PPAT beserta checklist berkas syarat warkah',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => true,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
            ],
            'Admin Keuangan' => [
                [
                    'menu_name' => 'Dashboard Finansial Kantor',
                    'menu_desc' => 'Analitik arus kas masuk/keluar, total omset jasa, saldo titipan PNBP, dan piutang klien',
                    'has_create' => false, 'has_read' => true, 'has_update' => false, 'has_delete' => false,
                    'has_grafik' => true, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Tagihan & Invoice Biaya Akta',
                    'menu_desc' => 'Penerbitan faktur tagihan jasa hukum notaris/PPAT, termin pembayaran, dan kirim nota',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => true,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Buku Kas Umum (Pemasukan & Pengeluaran)',
                    'menu_desc' => 'Pencatatan kas operasional kantor, operasional lapangan, ATK, dan rekonsiliasi',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => true,
                    'has_grafik' => true, 'has_box_dashboard' => true, 'has_import' => true, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Rekening Escrow & Titipan Biaya Pajak/PNBP',
                    'menu_desc' => 'Penatausahaan dana titipan klien khusus untuk penyetoran pajak BPHTB, PPh, dan billing PNBP',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Kuitansi & Bukti Penerimaan Pembayaran',
                    'menu_desc' => 'Penerbitan kuitansi resmi bertanda tangan digital notaris untuk klien',
                    'has_create' => true, 'has_read' => true, 'has_update' => false, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
            ],
            'Staf Notaris' => [
                [
                    'menu_name' => 'Daftar Tugas & Antrean Berkas',
                    'menu_desc' => 'Daftar antrean pekerjaan berkas permohonan akta notaris yang ditugaskan kepada staf',
                    'has_create' => false, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Verifikasi Dokumen & Warkah Klien',
                    'menu_desc' => 'Pemeriksaan kelengkapan dokumen KTP, KK, NPWP, legalitas badan usaha, dan riwayat tanah',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => true, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Penyusunan Minuta & Draf Akta',
                    'menu_desc' => 'Pembuatan draft akta kenotariatan (Pendirian PT/CV, Perjanjian Kredit, Kuasa, dll)',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Input Catatan Harian Kerja',
                    'menu_desc' => 'Log harian pengerjaan akta, koordinasi dengan klien, dan pencatatan kendala lapangan',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Portofolio & Arsip Akta Selesai',
                    'menu_desc' => 'Penyimpanan arsip salinan akta yang telah ditandatangani dan dijilid rapi',
                    'has_create' => false, 'has_read' => true, 'has_update' => false, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
            ],
            'Staf PPAT' => [
                [
                    'menu_name' => 'Antrean Berkas Pertanahan',
                    'menu_desc' => 'Pekerjaan permohonan peralihan hak tanah (AJB, Hibah, Waris, HT) yang ditugaskan',
                    'has_create' => false, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => true, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Pengecekan Sertifikat & Validasi Pajak',
                    'menu_desc' => 'Tracking status sertifikat di BPN, input bukti setor BPHTB & PPh Final',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => true, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Drafing Akta PPAT & Warkah Pendukung',
                    'menu_desc' => 'Penyusunan minuta akta PPAT resmi, surat pernyataan tidak sengketa, dan permohonan balik nama',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => true,
                ],
                [
                    'menu_name' => 'Catatan Progres Lapangan & Loket BPN',
                    'menu_desc' => 'Pencatatan aktivitas pengurusan berkas ke loket kantor BPN atau instansi terkait',
                    'has_create' => true, 'has_read' => true, 'has_update' => true, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
                [
                    'menu_name' => 'Portofolio & Riwayat PPAT',
                    'menu_desc' => 'Arsip berkas tanah yang telah selesai balik nama, roya, atau terbit sertifikat baru',
                    'has_create' => false, 'has_read' => true, 'has_update' => false, 'has_delete' => false,
                    'has_grafik' => false, 'has_box_dashboard' => false, 'has_import' => false, 'has_export' => true,
                    'has_filter' => true, 'has_print' => false,
                ],
            ],
        ];

        $menuOrder = 0;
        foreach ($menuMapping as $roleName => $menuItems) {
            if (isset($roleEntities[$roleName])) {
                $r = $roleEntities[$roleName];
                foreach ($menuItems as $m) {
                    \App\Models\ProjectMenu::updateOrCreate(
                        [
                            'project_role_id' => $r->id,
                            'menu_name' => $m['menu_name'],
                        ],
                        array_merge([
                            'price' => $menuPrice,
                            'is_done' => true,
                            'completed_date' => date('Y-m-d'),
                            'pic' => 'Muhammad Iqbal Murtadho',
                            'pic_user_id' => 2,
                            'sort_order' => $menuOrder++,
                        ], $m)
                    );
                }
            }
        }

        $project->recalculateGrandTotal();
    }
}
