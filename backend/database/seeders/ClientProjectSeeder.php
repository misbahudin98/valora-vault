<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ClientProjectPayment;
use App\Models\ProjectDatabaseTable;
use App\Models\ProjectMenu;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientProjectSeeder extends Seeder
{
    /**
     * Seed initial demo clients and projects into Valora OS.
     */
    public function run(): void
    {
        // Proteksi Produksi: Dilarang menyuntikkan data dummy jika tidak secara eksplisit diizinkan
        if (! filter_var(config('app.seed_demo_clients', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->info('[PRODUKSI] Pembuatan data dummy klien dilewati. Basis data tetap bersih untuk produksi.');
            return;
        }

        // Bersihkan data awal jika seeder dijalankan ulang
        ClientProjectPayment::query()->delete();
        ProjectDatabaseTable::query()->delete();
        ProjectMenu::query()->delete();
        ProjectRole::query()->delete();
        ClientProject::query()->delete();
        Client::query()->delete();

        $demos = [
            [
                'client' => [
                    'name' => 'PT Nusantara Digital Prima',
                    'contact_person' => 'Bpk. Hendra Wijaya',
                    'phone' => '0812-3456-7890',
                    'email' => 'hendra@nusantaraprima.co.id',
                    'address' => 'Jl. Jenderal Sudirman Kav. 45, Jakarta Selatan',
                    'industry' => 'Keuangan & ERP',
                ],
                'project' => [
                    'project_name' => 'Sistem ERP Multi-Cabang Terintegrasi',
                    'app_name' => 'Sistem ERP Multi-Cabang Terintegrasi',
                    'project_type' => 'development',
                    'lead_pic' => 'Muhammad Iqbal Murtadho',
                    'github_url' => 'https://github.com/nusantara-prima/erp-system',
                    'app_desc' => 'Sistem ERP Terpadu Untuk Manajemen Cabang, Pergudangan, Arus Kas Keuangan, Dan Otorisasi Transaksi Real-Time.',
                    'use_landing' => true,
                    'use_login' => true,
                    'use_register' => true,
                    'use_app_core' => true,
                    'landing_price' => 2500000,
                    'login_price' => 1000000,
                    'register_price' => 1000000,
                    'status' => 'Aktif',
                    'grand_total' => 10500000,
                    'paid_amount' => 5250000,
                ],
                'roles' => [
                    [
                        'name' => 'Super Admin',
                        'menus' => [
                            [
                                'name' => 'Dashboard Monitoring Global',
                                'desc' => 'Panel metrik performa seluruh kantor cabang',
                                'pic' => 'Muhammad Iqbal Murtadho',
                                'price' => 900000,
                                'is_done' => true,
                                'has_create' => false,
                                'has_read' => true,
                                'has_update' => false,
                                'has_delete' => false,
                                'has_grafik' => true,
                                'has_box_dashboard' => true,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => true,
                                'has_custom_logic' => false,
                            ],
                            [
                                'name' => 'Manajemen Hak Akses & User',
                                'desc' => 'Otorisasi permission role per modul aplikasi',
                                'pic' => 'Misbahudin',
                                'price' => 900000,
                                'is_done' => true,
                                'has_create' => true,
                                'has_read' => true,
                                'has_update' => true,
                                'has_delete' => true,
                                'has_grafik' => false,
                                'has_box_dashboard' => false,
                                'has_import' => true,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => false,
                                'has_notification' => false,
                                'has_custom_logic' => false,
                            ],
                            [
                                'name' => 'Audit Trail & Log Keamanan',
                                'desc' => 'Pencatatan rekam jejak aktivitas sensitif sistem',
                                'pic' => 'Misbahudin',
                                'price' => 900000,
                                'is_done' => false,
                                'has_create' => false,
                                'has_read' => true,
                                'has_update' => false,
                                'has_delete' => false,
                                'has_grafik' => true,
                                'has_box_dashboard' => false,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => true,
                                'has_custom_logic' => false,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Manager Keuangan',
                        'menus' => [
                            [
                                'name' => 'Dashboard Arus Kas & Neraca',
                                'desc' => 'Laporan laba rugi dan buku besar real-time',
                                'pic' => 'Muhammad Iqbal Murtadho',
                                'price' => 900000,
                                'is_done' => true,
                                'has_create' => false,
                                'has_read' => true,
                                'has_update' => false,
                                'has_delete' => false,
                                'has_grafik' => true,
                                'has_box_dashboard' => true,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => false,
                                'has_custom_logic' => false,
                            ],
                            [
                                'name' => 'Rekonsiliasi Bank Otomatis',
                                'desc' => 'Sinkronisasi mutasi rekening BCA & Mandiri',
                                'pic' => 'Misbahudin',
                                'price' => 900000,
                                'is_done' => false,
                                'has_create' => true,
                                'has_read' => true,
                                'has_update' => true,
                                'has_delete' => false,
                                'has_grafik' => false,
                                'has_box_dashboard' => false,
                                'has_import' => true,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => true,
                                'has_custom_logic' => false,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Kepala Gudang',
                        'menus' => [
                            [
                                'name' => 'Stok Opname & Mutasi Barang',
                                'desc' => 'Pencatatan pergerakan barang antar gudang',
                                'pic' => 'Misbahudin',
                                'price' => 900000,
                                'is_done' => false,
                                'has_create' => true,
                                'has_read' => true,
                                'has_update' => true,
                                'has_delete' => true,
                                'has_grafik' => true,
                                'has_box_dashboard' => true,
                                'has_import' => true,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => false,
                                'has_custom_logic' => false,
                            ],
                        ],
                    ],
                ],
                'tables' => [
                    ['name' => 'users', 'price' => 100000, 'done' => true],
                    ['name' => 'roles_permissions', 'price' => 100000, 'done' => true],
                    ['name' => 'branch_offices', 'price' => 100000, 'done' => true],
                    ['name' => 'inventory_items', 'price' => 100000, 'done' => true],
                    ['name' => 'inventory_mutations', 'price' => 100000, 'done' => false],
                    ['name' => 'general_ledger', 'price' => 100000, 'done' => false],
                ],
            ],
            [
                'client' => [
                    'name' => 'PT Solusi Finansial Mandiri',
                    'contact_person' => 'Ibu Ratna Dewi',
                    'phone' => '0813-9876-5432',
                    'email' => 'ratna@finansialmandiri.id',
                    'address' => 'SCBD One Pacific Place Lt. 15, Jakarta',
                    'industry' => 'Fintech & P2P',
                ],
                'project' => [
                    'project_name' => 'Platform Core Fintech & Peer-To-Peer Lending',
                    'app_name' => 'Platform Core Fintech & Peer-To-Peer Lending',
                    'project_type' => 'development',
                    'lead_pic' => 'Misbahudin',
                    'github_url' => 'https://github.com/finansial-mandiri/core-p2p',
                    'app_desc' => 'Platform P2P Lending berlisensi OJK dengan modul verifikasi KYC otomatis, scoring kredit AI, dan integrasi Payment Gateway.',
                    'use_landing' => true,
                    'use_login' => true,
                    'use_register' => true,
                    'use_app_core' => true,
                    'landing_price' => 3000000,
                    'login_price' => 1000000,
                    'register_price' => 1000000,
                    'status' => 'Aktif',
                    'grand_total' => 14500000,
                    'paid_amount' => 14500000,
                ],
                'roles' => [
                    [
                        'name' => 'Super Admin & Risk Analyst',
                        'menus' => [
                            [
                                'name' => 'Credit Scoring & AI Risk Engine',
                                'desc' => 'Algoritma mitigasi risiko pinjaman dan evaluasi scoring kredit',
                                'pic' => 'Misbahudin',
                                'price' => 1750000,
                                'is_done' => true,
                                'has_create' => true,
                                'has_read' => true,
                                'has_update' => true,
                                'has_delete' => true,
                                'has_grafik' => true,
                                'has_box_dashboard' => true,
                                'has_import' => true,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => true,
                                'has_custom_logic' => true,
                            ],
                            [
                                'name' => 'KYC & Biometric Identity Verification',
                                'desc' => 'Otorisasi identitas nasabah, verifikasi Dukcapil & biometrik liveness',
                                'pic' => 'Misbahudin',
                                'price' => 1450000,
                                'is_done' => true,
                                'has_create' => true,
                                'has_read' => true,
                                'has_update' => true,
                                'has_delete' => false,
                                'has_grafik' => false,
                                'has_box_dashboard' => false,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => true,
                                'has_custom_logic' => true,
                            ],
                            [
                                'name' => 'OJK Compliance & Audit Report',
                                'desc' => 'Pelaporan regulasi bulanan FDC OJK dan jejak audit kepatuhan',
                                'pic' => 'Misbahudin',
                                'price' => 1400000,
                                'is_done' => true,
                                'has_create' => false,
                                'has_read' => true,
                                'has_update' => false,
                                'has_delete' => false,
                                'has_grafik' => true,
                                'has_box_dashboard' => true,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => false,
                                'has_custom_logic' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Loan & Operations Officer',
                        'menus' => [
                            [
                                'name' => 'Loan Origination & Disbursement',
                                'desc' => 'Pengajuan pinjaman, underwriting dan pencairan via Payment Gateway',
                                'pic' => 'Misbahudin',
                                'price' => 1450000,
                                'is_done' => true,
                                'has_create' => true,
                                'has_read' => true,
                                'has_update' => true,
                                'has_delete' => false,
                                'has_grafik' => false,
                                'has_box_dashboard' => false,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => true,
                                'has_custom_logic' => true,
                            ],
                            [
                                'name' => 'Penyelesaian Kolektibilitas & NPL',
                                'desc' => 'Monitoring status keterlambatan cicilan, penagihan dan restrukturisasi',
                                'pic' => 'Misbahudin',
                                'price' => 1350000,
                                'is_done' => true,
                                'has_create' => false,
                                'has_read' => true,
                                'has_update' => true,
                                'has_delete' => false,
                                'has_grafik' => false,
                                'has_box_dashboard' => false,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => true,
                                'has_notification' => true,
                                'has_custom_logic' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Lender & Investor Portal',
                        'menus' => [
                            [
                                'name' => 'Portfolio & Yield Investment Dashboard',
                                'desc' => 'Metrik imbal hasil pendana, saldo escrow, dan reinvestasi otomatis',
                                'pic' => 'Misbahudin',
                                'price' => 1400000,
                                'is_done' => true,
                                'has_create' => false,
                                'has_read' => true,
                                'has_update' => false,
                                'has_delete' => false,
                                'has_grafik' => true,
                                'has_box_dashboard' => true,
                                'has_import' => false,
                                'has_export' => true,
                                'has_filter' => true,
                                'has_print' => false,
                                'has_notification' => true,
                                'has_custom_logic' => true,
                            ],
                        ],
                    ],
                ],
                'tables' => [
                    ['name' => 'borrower_profiles', 'price' => 100000, 'done' => true],
                    ['name' => 'loan_applications', 'price' => 100000, 'done' => true],
                    ['name' => 'credit_score_logs', 'price' => 100000, 'done' => true],
                    ['name' => 'kyc_verifications', 'price' => 100000, 'done' => true],
                    ['name' => 'escrow_disbursements', 'price' => 100000, 'done' => true],
                    ['name' => 'lender_portfolios', 'price' => 100000, 'done' => true],
                    ['name' => 'ojk_regulatory_logs', 'price' => 100000, 'done' => true],
                ],
            ],
        ];

        foreach ($demos as $demo) {
            $client = Client::create($demo['client']);
            $projectData = $demo['project'];
            $projectData['client_id'] = $client->id;

            $leadUser = User::where('name', $projectData['lead_pic'] ?? '')->first();
            if ($leadUser) {
                $projectData['lead_pic_id'] = $leadUser->id;
            }

            $project = ClientProject::create($projectData);

            // Hubungkan pekerja ke proyek via tabel relasi client_project_workers
            $allStaff = User::all();
            if ($allStaff->isNotEmpty()) {
                $workerSync = [];
                foreach ($allStaff as $staff) {
                    $roleInProj = ($staff->name === $project->lead_pic) ? 'Lead PIC' : ($staff->jabatan ?: 'Pekerja');
                    $workerSync[$staff->id] = ['role_in_project' => $roleInProj];
                }
                $project->workers()->sync($workerSync);
            }

            $order = 0;
            foreach ($demo['roles'] as $roleData) {
                $role = ProjectRole::create([
                    'project_id' => $project->id,
                    'role_name' => $roleData['name'],
                    'sort_order' => $order++,
                ]);

                $menuSort = 0;
                foreach ($roleData['menus'] ?? [] as $m) {
                    $picUser = User::where('name', $m['pic'] ?? $project->lead_pic)->first();

                    ProjectMenu::create([
                        'project_role_id' => $role->id,
                        'menu_name' => $m['name'],
                        'menu_desc' => $m['desc'] ?? null,
                        'pic' => $m['pic'] ?? $project->lead_pic,
                        'pic_user_id' => $picUser?->id,
                        'price' => (float) ($m['price'] ?? 750000),
                        'is_done' => (bool) ($m['is_done'] ?? false),
                        'has_create' => (bool) ($m['has_create'] ?? true),
                        'has_read' => (bool) ($m['has_read'] ?? true),
                        'has_update' => (bool) ($m['has_update'] ?? true),
                        'has_delete' => (bool) ($m['has_delete'] ?? true),
                        'has_grafik' => (bool) ($m['has_grafik'] ?? false),
                        'has_box_dashboard' => (bool) ($m['has_box_dashboard'] ?? false),
                        'has_import' => (bool) ($m['has_import'] ?? false),
                        'has_export' => (bool) ($m['has_export'] ?? false),
                        'has_filter' => (bool) ($m['has_filter'] ?? true),
                        'has_print' => (bool) ($m['has_print'] ?? false),
                        'has_notification' => (bool) ($m['has_notification'] ?? false),
                        'has_custom_logic' => (bool) ($m['has_custom_logic'] ?? false),
                        'sort_order' => $menuSort++,
                    ]);
                }
            }

            foreach ($demo['tables'] ?? [] as $t) {
                ProjectDatabaseTable::create([
                    'project_id' => $project->id,
                    'table_name' => $t['name'],
                    'price' => (float) ($t['price'] ?? 100000),
                    'is_done' => (bool) ($t['done'] ?? false),
                ]);
            }

            $project->recalculateGrandTotal();

            $initialPaid = (float) ($projectData['paid_amount'] ?? 0);
            if ($initialPaid > 0) {
                ClientProjectPayment::create([
                    'project_id' => $project->id,
                    'amount' => $initialPaid,
                    'payment_date' => now()->subDays(12)->format('Y-m-d'),
                    'payment_method' => 'Transfer BCA',
                    'reference_no' => $initialPaid >= $project->grand_total ? 'Pelunasan 100% Kontrak (TRX-BCA-9921)' : 'Pembayaran Uang Muka DP 50% (TRX-BCA-88912)',
                    'recorded_by' => 'Muhammad Iqbal Murtadho',
                ]);
                $project->recalculatePaidAmount();
            }
        }
    }
}
