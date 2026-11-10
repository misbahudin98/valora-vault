<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\MasterBillingRate;
use App\Models\User;
use Database\Seeders\MasterBillingRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAndMasterRateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => ['admin'],
            'is_active' => true,
        ]);

        $this->seed(MasterBillingRateSeeder::class);
    }

    public function test_admin_can_get_master_billing_rates(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/billing-rates');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'rate_key', 'name', 'unit', 'price', 'is_active'],
                ],
                'rates_map' => [
                    'landing',
                    'login',
                    'register',
                    'menu_price',
                    'database_table_price',
                    'maintenance_menu_price',
                ],
                'histories',
            ]);

        $this->assertEquals(500000.00, $response->json('rates_map.landing'));
        $this->assertEquals(250000.00, $response->json('rates_map.menu_price'));
    }

    public function test_admin_can_update_master_billing_rate_with_reason(): void
    {
        $payload = [
            'price' => 850000.00,
            'reason' => 'Penyesuaian kompleksitas modul Svelte 5 runes dan integrasi API RFC 7807',
        ];

        $response = $this->actingAs($this->admin)->putJson('/api/v1/admin/billing-rates/menu_price', $payload);

        $response->assertOk()
            ->assertJsonPath('data.price', 850000);

        $this->assertDatabaseHas('master_billing_rates', [
            'rate_key' => 'menu_price',
            'price' => 850000.00,
        ]);

        $this->assertDatabaseHas('billing_rate_histories', [
            'rate_key' => 'menu_price',
            'old_price' => 250000.00,
            'new_price' => 850000.00,
            'reason' => 'Penyesuaian kompleksitas modul Svelte 5 runes dan integrasi API RFC 7807',
        ]);
    }

    public function test_update_rate_requires_reason(): void
    {
        $payload = [
            'price' => 900000.00,
            // reason missing
        ];

        $response = $this->actingAs($this->admin)->putJson('/api/v1/admin/billing-rates/menu_price', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_admin_can_get_client_billing_overview(): void
    {
        $client = Client::create([
            'name' => 'PT Surya Graha Mandiri',
            'industry' => 'Properti & Konstruksi',
            'phone' => '08123456789',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Sistem ERP Properti',
            'app_name' => 'Surya ERP',
            'project_type' => 'development',
            'lead_pic' => 'Ahmad Fauzi',
            'grand_total' => 15000000.00,
            'paid_amount' => 5000000.00,
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/billing/projects');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'app_name',
                        'client',
                        'grand_total',
                        'paid_amount',
                        'remaining_amount',
                        'payment_status',
                    ],
                ],
                'meta' => [
                    'total_projects',
                    'total_grand',
                    'total_paid',
                    'total_remaining',
                ],
            ]);

        $this->assertEquals(15000000.00, $response->json('meta.total_grand'));
        $this->assertEquals(5000000.00, $response->json('meta.total_paid'));
        $this->assertEquals(10000000.00, $response->json('meta.total_remaining'));
    }

    public function test_admin_can_get_project_invoice_breakdown(): void
    {
        $client = Client::create([
            'name' => 'Klinik Medika Farma',
            'industry' => 'Kesehatan',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Aplikasi Rekam Medis',
            'app_name' => 'Medika Cloud',
            'project_type' => 'development',
            'use_landing' => true,
            'landing_price' => 2500000.00,
            'use_login' => true,
            'login_price' => 1000000.00,
            'grand_total' => 4500000.00,
            'paid_amount' => 0.00,
            'status' => 'Aktif',
        ]);

        $role = \App\Models\ProjectRole::create([
            'project_id' => $project->id,
            'role_name' => 'Dokter',
            'sort_order' => 1,
        ]);

        \App\Models\ProjectMenu::create([
            'project_role_id' => $role->id,
            'menu_name' => 'Rekam Medis Pasien',
            'menu_desc' => 'Pencatatan riwayat diagnosis dan resep obat',
            'price' => 1000000.00,
            'has_create' => true,
            'has_read' => true,
            'has_update' => true,
            'has_delete' => false,
            'has_filter' => true,
            'has_print' => true,
            'has_export' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/admin/billing/projects/{$project->id}/invoice");

        $response->assertOk()
            ->assertJsonPath('project.app_name', 'Medika Cloud')
            ->assertJsonPath('project.grand_total', 4500000)
            ->assertJsonPath('project.payment_status', 'Belum Bayar')
            ->assertJsonCount(3, 'items')
            ->assertJsonPath('items.2.category', 'Modul [Dokter]')
            ->assertJsonPath('items.2.capabilities', ['Create', 'Read', 'Update', 'Export', 'Filter', 'Print']);
    }

    public function test_admin_can_record_project_payment_and_update_balance(): void
    {
        $client = Client::create([
            'name' => 'CV Logistik Sentosa',
            'industry' => 'Logistik & Ekspedisi',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Fleet Management',
            'app_name' => 'Sentosa Fleet',
            'project_type' => 'development',
            'grand_total' => 10000000.00,
            'paid_amount' => 0.00,
            'status' => 'Aktif',
        ]);

        $paymentPayload = [
            'amount' => 4000000.00,
            'payment_date' => '2026-02-01',
            'payment_method' => 'Transfer BCA',
            'reference_no' => 'DP 40% Termin 1',
        ];

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/billing/projects/{$project->id}/payments", $paymentPayload);

        $response->assertStatus(201)
            ->assertJsonPath('paid_amount', 4000000)
            ->assertJsonPath('remaining_amount', 6000000);

        $this->assertDatabaseHas('client_project_payments', [
            'project_id' => $project->id,
            'amount' => 4000000.00,
            'payment_method' => 'Transfer BCA',
            'reference_no' => 'DP 40% Termin 1',
        ]);

        $this->assertEquals(4000000.00, (float) $project->fresh()->paid_amount);
    }

    public function test_payment_exceeding_remaining_balance_is_rejected_with_sensible_error(): void
    {
        $client = Client::create([
            'name' => 'PT Surya Mega Kreasi',
            'industry' => 'Advertising',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Portal Iklan',
            'app_name' => 'Surya Ads',
            'project_type' => 'development',
            'grand_total' => 5000000.00,
            'paid_amount' => 4000000.00, // sisa 1.000.000
            'status' => 'Aktif',
        ]);

        // Attempt to pay 2.000.000 (exceeds remaining 1.000.000)
        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/billing/projects/{$project->id}/payments", [
            'amount' => 2000000.00,
            'payment_date' => '2026-02-05',
            'payment_method' => 'Transfer BCA',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('title', 'Nominal Melebihi Sisa Tagihan');
    }

    public function test_admin_can_delete_project_payment_and_restore_balance(): void
    {
        $client = Client::create([
            'name' => 'PT Mitra Finansial Utama',
            'industry' => 'Fintech',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Core Finansial',
            'app_name' => 'Mitra Core',
            'project_type' => 'development',
            'grand_total' => 8000000.00,
            'paid_amount' => 0.00,
            'status' => 'Aktif',
        ]);

        // 1. Record payment of 3.000.000
        $resPay = $this->actingAs($this->admin)->postJson("/api/v1/admin/billing/projects/{$project->id}/payments", [
            'amount' => 3000000.00,
            'payment_date' => '2026-02-02',
            'payment_method' => 'Transfer BCA',
            'reference_no' => 'TRX-DP-01',
        ]);
        $resPay->assertStatus(201);
        $paymentId = (int) $resPay->json('payment.id');
        $this->assertEquals(3000000.00, (float) $project->fresh()->paid_amount);

        // 2. Delete payment
        $resDel = $this->actingAs($this->admin)->deleteJson("/api/v1/admin/billing/payments/{$paymentId}");
        $resDel->assertOk()
            ->assertJsonPath('paid_amount', 0)
            ->assertJsonPath('remaining_amount', 8000000);

        $this->assertDatabaseMissing('client_project_payments', [
            'id' => $paymentId,
        ]);

        // 3. Project balance restored to 0 paid and 8.000.000 remaining
        $this->assertEquals(0.00, (float) $project->fresh()->paid_amount);
    }

    public function test_admin_can_view_official_payment_receipt(): void
    {
        $client = Client::create([
            'name' => 'PT Prima Solusindo Digital',
            'contact_person' => 'Bpk. Ridwan',
            'phone' => '081234567890',
            'industry' => 'IT Solution',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Sistem Kuitansi',
            'app_name' => 'Kuitansi Cloud',
            'project_type' => 'development',
            'grand_total' => 6000000.00,
            'paid_amount' => 0.00,
            'status' => 'Aktif',
        ]);

        $resPay = $this->actingAs($this->admin)->postJson("/api/v1/admin/billing/projects/{$project->id}/payments", [
            'amount' => 3000000.00,
            'payment_date' => '2026-02-10',
            'payment_method' => 'Transfer BCA',
            'reference_no' => 'Pembayaran DP 50%',
        ]);
        $resPay->assertStatus(201);
        $paymentId = (int) $resPay->json('payment.id');

        $resReceipt = $this->actingAs($this->admin)->getJson("/api/v1/admin/billing/payments/{$paymentId}/receipt");
        $resReceipt->assertOk()
            ->assertJsonPath('receipt.amount', 3000000)
            ->assertJsonPath('receipt.payment_method', 'Transfer BCA')
            ->assertJsonPath('project.app_name', 'Kuitansi Cloud')
            ->assertJsonPath('client.name', 'PT Prima Solusindo Digital')
            ->assertJsonPath('company.name', 'PT Valora Digital Solusindo');
    }

    public function test_project_and_menu_creation_connects_to_active_master_rates(): void
    {
        // 1. Update master rates
        MasterBillingRate::where('rate_key', 'landing')->update(['price' => 3000000.00]);
        MasterBillingRate::where('rate_key', 'menu_price')->update(['price' => 850000.00]);
        MasterBillingRate::where('rate_key', 'database_table_price')->update(['price' => 120000.00]);

        // 2. Create project with use_landing = true, without explicit landing_price
        $projectPayload = [
            'mode' => 'new_client',
            'name' => 'PT Dinamika Solusi Digital',
            'contact_person' => 'Budi Santoso',
            'phone' => '081299998888',
            'app_name' => 'ERP Dinamika',
            'project_name' => 'ERP Dinamika',
            'project_type' => 'development',
            'industry' => 'Teknologi Informasi',
            'use_landing' => true,
            'use_login' => false,
            'use_register' => false,
            'use_app_core' => true,
            'roles' => ['Superadmin'],
        ];

        $resProject = $this->actingAs($this->admin)->postJson('/api/v1/admin/clients', $projectPayload);
        $resProject->assertStatus(201);
        $projectId = (int) $resProject->json('data.id');
        $roleId = (int) $resProject->json('data.roles.0.id');

        $this->assertEquals(3000000.00, (float) $resProject->json('data.landing_price'));

        // 3. Add menu to role without explicit price
        $menuPayload = [
            'menu_name' => 'Master Transaksi',
            'pic' => 'Misbahudin',
        ];

        $resMenu = $this->actingAs($this->admin)->postJson("/api/v1/admin/roles/{$roleId}/menus", $menuPayload);
        $resMenu->assertStatus(201);
        $this->assertEquals(850000.00, (float) $resMenu->json('data.price'));

        // 4. Add database table to project without explicit price
        $tablePayload = [
            'table_name' => 'transactions',
        ];

        $resTable = $this->actingAs($this->admin)->postJson("/api/v1/admin/projects/{$projectId}/database-tables", $tablePayload);
        $resTable->assertStatus(201);
        $this->assertEquals(120000.00, (float) $resTable->json('data.price'));

        // 5. Verify project grand total recalculated accurately: 3,000,000 + 850,000 + 120,000 = 3,970,000
        $project = ClientProject::findOrFail($projectId);
        $this->assertEquals(3970000.00, (float) $project->grand_total);
    }

    public function test_admin_can_store_new_registered_module_rate_with_12_capabilities(): void
    {
        $payload = [
            'name' => 'Modul Approval Bertingkat',
            'category' => 'registered_module',
            'price' => 1250000.00,
            'unit' => 'per menu modul',
            'description' => 'Modul approval hierarki dinamis dengan notifikasi WA dan cetak lembar persetujuan.',
            'has_create' => true,
            'has_read' => true,
            'has_update' => true,
            'has_delete' => false,
            'has_grafik' => true,
            'has_box_dashboard' => true,
            'has_import' => false,
            'has_export' => true,
            'has_filter' => true,
            'has_print' => true,
            'has_notification' => true,
            'has_custom_logic' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/billing-rates', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Modul Approval Bertingkat')
            ->assertJsonPath('data.category', 'registered_module')
            ->assertJsonPath('data.price', 1250000)
            ->assertJsonPath('data.has_notification', true)
            ->assertJsonPath('data.has_custom_logic', true);

        $this->assertDatabaseHas('master_billing_rates', [
            'name' => 'Modul Approval Bertingkat',
            'price' => 1250000.00,
            'has_notification' => 1,
            'has_custom_logic' => 1,
        ]);
    }

    public function test_admin_cannot_delete_protected_core_rate(): void
    {
        $response = $this->actingAs($this->admin)->deleteJson('/api/v1/admin/billing-rates/landing');

        $response->assertStatus(422)
            ->assertJsonPath('title', 'Tarif Inti Dilindungi');

        $this->assertDatabaseHas('master_billing_rates', [
            'rate_key' => 'landing',
        ]);
    }

    public function test_admin_can_delete_registered_module_rate(): void
    {
        $customRate = MasterBillingRate::create([
            'rate_key' => 'modul_custom_test',
            'name' => 'Modul Custom Test',
            'category' => 'registered_module',
            'price' => 950000.00,
            'unit' => 'per menu',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/v1/admin/billing-rates/modul_custom_test');

        $response->assertOk()
            ->assertJsonPath('message', "Katalog modul menu terdaftar 'Modul Custom Test' berhasil dihapus dari Master Tarif.");

        $this->assertDatabaseMissing('master_billing_rates', [
            'rate_key' => 'modul_custom_test',
        ]);

        $this->assertDatabaseHas('billing_rate_histories', [
            'rate_key' => 'modul_custom_test',
            'new_price' => 0.0,
        ]);
    }

    public function test_deleting_registered_module_rate_does_not_affect_existing_project_menus_or_payments(): void
    {
        // 1. Create a registered module rate
        $rate = MasterBillingRate::create([
            'rate_key' => 'modul_pos_kasir',
            'name' => 'Modul Kasir POS',
            'category' => 'registered_module',
            'price' => 1500000.00,
            'unit' => 'per menu modul',
            'is_active' => true,
        ]);

        // 2. Create client & project
        $client = Client::create([
            'name' => 'Toko Sumber Rejeki',
            'industry' => 'Retail',
            'phone' => '08123445566',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'POS Toko Sumber Rejeki',
            'app_name' => 'Sumber POS',
            'project_type' => 'development',
            'project_status' => 'deal',
            'total_valuation' => 1500000.00,
            'grand_total' => 1500000.00,
            'paid_amount' => 1500000.00,
            'remaining_balance' => 0.00,
        ]);

        $role = $project->roles()->create(['role_name' => 'Kasir']);

        // 3. Create menu attached with this module snapshot price
        $menu = $role->menus()->create([
            'menu_name' => 'Transaksi Penjualan POS',
            'module_type' => 'modul_pos_kasir',
            'price' => 1500000.00,
            'has_create' => true,
            'has_notification' => true,
            'has_custom_logic' => true,
        ]);

        // 4. Record payment (lunas)
        $payment = $project->payments()->create([
            'amount' => 1500000.00,
            'payment_date' => '2026-03-01',
            'payment_method' => 'Transfer Mandiri',
            'reference_no' => 'Pelunasan POS',
        ]);

        // 5. Admin deletes the registered module rate from Master Tarif
        $resDelete = $this->actingAs($this->admin)->deleteJson('/api/v1/admin/billing-rates/modul_pos_kasir');
        $resDelete->assertOk();

        // 6. Verify Master Rate is deleted, but Project Menu, Valuation, and Payment remain completely intact!
        $this->assertDatabaseMissing('master_billing_rates', ['rate_key' => 'modul_pos_kasir']);

        $refreshedMenu = $menu->fresh();
        $this->assertNotNull($refreshedMenu);
        $this->assertEquals(1500000.00, (float) $refreshedMenu->price);
        $this->assertEquals('modul_pos_kasir', $refreshedMenu->module_type);
        $this->assertTrue((bool) $refreshedMenu->has_notification);
        $this->assertTrue((bool) $refreshedMenu->has_custom_logic);

        $refreshedProject = $project->fresh();
        $this->assertEquals(1500000.00, (float) $refreshedProject->grand_total);
        $this->assertEquals(1500000.00, (float) $refreshedProject->paid_amount);
        $this->assertEquals(0.00, (float) $refreshedProject->remaining_balance);

        $refreshedPayment = $payment->fresh();
        $this->assertNotNull($refreshedPayment);
        $this->assertEquals(1500000.00, (float) $refreshedPayment->amount);
    }

    public function test_project_landing_login_register_capabilities_can_be_stored_and_retrieved(): void
    {
        $client = Client::create([
            'name' => 'PT Prima Solusi Terpadu',
            'industry' => 'Konsultan Bisnis',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Portal Konsultasi Prima',
            'app_name' => 'Prima Portal',
            'project_type' => 'development',
            'use_landing' => true,
            'use_login' => true,
            'use_register' => true,
            'landing_price' => 2500000.00,
            'login_price' => 1000000.00,
            'register_price' => 1000000.00,
            'grand_total' => 4500000.00,
            'paid_amount' => 0.00,
            'status' => 'Aktif',
        ]);

        $updatePayload = [
            'landing_capabilities' => [
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
            ],
            'login_capabilities' => [
                'has_create' => true,
                'has_read' => true,
                'has_update' => true,
                'has_delete' => false,
                'has_grafik' => false,
                'has_box_dashboard' => false,
                'has_import' => false,
                'has_export' => false,
                'has_filter' => false,
                'has_print' => false,
            ],
            'register_capabilities' => [
                'has_create' => true,
                'has_read' => true,
                'has_update' => false,
                'has_delete' => false,
                'has_grafik' => false,
                'has_box_dashboard' => false,
                'has_import' => false,
                'has_export' => false,
                'has_filter' => false,
                'has_print' => false,
            ],
        ];

        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/clients/{$project->id}", $updatePayload);
        $response->assertOk();

        $refreshed = $project->fresh();
        $this->assertTrue((bool) $refreshed->landing_capabilities['has_grafik']);
        $this->assertTrue((bool) $refreshed->landing_capabilities['has_box_dashboard']);
        $this->assertTrue((bool) $refreshed->login_capabilities['has_create']);
        $this->assertTrue((bool) $refreshed->register_capabilities['has_create']);

        // Check invoice endpoint reflects capabilities on Landing, Login, Register
        $resInvoice = $this->actingAs($this->admin)->getJson("/api/v1/admin/billing/projects/{$project->id}/invoice");
        $resInvoice->assertOk();
        $items = $resInvoice->json('items');
        $this->assertNotEmpty($items[0]['capabilities']);
        $this->assertContains('Grafik', $items[0]['capabilities']);
        $this->assertContains('Box Dashboard', $items[0]['capabilities']);
    }
}
