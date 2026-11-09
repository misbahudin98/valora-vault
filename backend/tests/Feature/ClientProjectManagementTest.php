<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ClientProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('sanctum.stateful', ['valora-vault.test', 'template.org', 'localhost']);
        Config::set('session.driver', 'file');
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'email' => 'admin@valora-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);
    }

    private function createRegularUser(): User
    {
        return User::factory()->create([
            'email' => 'staff@valora-vault.test',
            'role' => ['user'],
            'is_active' => true,
        ]);
    }

    public function test_admin_can_list_client_projects(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create([
            'name' => 'PT Nusantara Digital Prima',
            'contact_person' => 'Bpk. Hendra',
            'phone' => '0812-3456-7890',
            'industry' => 'Keuangan & ERP',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Sistem ERP Multi-Cabang',
            'app_name' => 'Sistem ERP Multi-Cabang',
            'project_type' => 'development',
            'status' => 'Aktif',
            'grand_total' => 4500000.0,
        ]);

        ProjectRole::create([
            'project_id' => $project->id,
            'role_name' => 'Super Admin',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/api/v1/admin/clients');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'client_id',
                    'project_name',
                    'app_name',
                    'project_type',
                    'status',
                    'grand_total',
                    'client' => ['id', 'name', 'industry'],
                    'roles' => [
                        '*' => ['id', 'role_name', 'sort_order']
                    ]
                ]
            ],
            'meta' => [
                'total_projects',
                'total_clients',
                'active_projects',
                'development_projects',
                'maintenance_projects',
                'total_valuation',
                'total_paid',
                'limit',
            ]
        ]);
        $this->assertEquals(1, $response->json('meta.total_projects'));
        $this->assertEquals(1, $response->json('meta.total_clients'));
    }

    public function test_admin_can_filter_projects_by_type_and_search(): void
    {
        $admin = $this->createAdminUser();

        $client1 = Client::create(['name' => 'PT Alpha Solusindo', 'industry' => 'Fintech']);
        $client2 = Client::create(['name' => 'CV Beta Logistik', 'industry' => 'Logistik']);

        ClientProject::create([
            'client_id' => $client1->id,
            'project_name' => 'Platform P2P Lending',
            'app_name' => 'Platform P2P Lending',
            'project_type' => 'development',
            'status' => 'Aktif',
        ]);

        ClientProject::create([
            'client_id' => $client2->id,
            'project_name' => 'Sistem Armada Truk',
            'app_name' => 'Sistem Armada Truk',
            'project_type' => 'maintenance',
            'status' => 'Aktif',
        ]);

        // Filter by project_type
        $resType = $this->actingAs($admin, 'web')->getJson('/api/v1/admin/clients?project_type=maintenance');
        $resType->assertStatus(200);
        $this->assertCount(1, $resType->json('data'));
        $this->assertEquals('Sistem Armada Truk', $resType->json('data.0.app_name'));

        // Search by client name
        $resSearch = $this->actingAs($admin, 'web')->getJson('/api/v1/admin/clients?search=Alpha');
        $resSearch->assertStatus(200);
        $this->assertCount(1, $resSearch->json('data'));
        $this->assertEquals('Platform P2P Lending', $resSearch->json('data.0.app_name'));
    }

    public function test_admin_can_create_new_client_and_project_with_roles(): void
    {
        $admin = $this->createAdminUser();

        $payload = [
            'mode' => 'new_client',
            'name' => 'PT Medika Harmoni',
            'contact_person' => 'dr. Sarah',
            'phone' => '0811-2233-4455',
            'email' => 'sarah@medika.com',
            'address' => 'Jl. Kesehatan No. 12',
            'industry' => 'Kesehatan',
            'app_name' => 'Sistem Rekam Medis Elektronik',
            'project_type' => 'development',
            'lead_pic' => 'Muhammad Iqbal Murtadho',
            'github_url' => 'https://github.com/medika/rme',
            'app_desc' => 'Sistem RME terintegrasi SatuSehat Kemenkes RI',
            'use_landing' => true,
            'landing_price' => 2500000,
            'use_login' => true,
            'login_price' => 1000000,
            'use_register' => true,
            'register_price' => 1000000,
            'roles' => ['Dokter Spesialis', 'Perawat Jaga', 'Petugas Farmasi'],
        ];

        $response = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/clients', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.app_name', 'Sistem Rekam Medis Elektronik');
        $response->assertJsonPath('data.client.name', 'PT Medika Harmoni');
        $this->assertEquals(4500000, $response->json('data.grand_total'));

        $this->assertDatabaseHas('clients', [
            'name' => 'PT Medika Harmoni',
            'industry' => 'Kesehatan',
        ]);

        $this->assertDatabaseHas('client_projects', [
            'app_name' => 'Sistem Rekam Medis Elektronik',
            'lead_pic' => 'Muhammad Iqbal Murtadho',
            'grand_total' => 4500000,
        ]);

        $this->assertDatabaseHas('project_roles', ['role_name' => 'Dokter Spesialis']);
        $this->assertDatabaseHas('project_roles', ['role_name' => 'Perawat Jaga']);
        $this->assertDatabaseHas('project_roles', ['role_name' => 'Petugas Farmasi']);
    }

    public function test_admin_can_create_additional_project_for_existing_client(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create([
            'name' => 'PT Semesta Edukasi',
            'industry' => 'Pendidikan',
        ]);

        $payload = [
            'mode' => 'existing_client',
            'client_id' => $client->id,
            'app_name' => 'SIAKAD Mobile App',
            'project_type' => 'maintenance',
            'lead_pic' => 'Misbahudin',
            'use_landing' => false,
            'use_login' => true,
            'login_price' => 1000000,
            'roles' => ['Dosen', 'Mahasiswa'],
        ];

        $response = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/clients', $payload);

        $response->assertStatus(201);
        $this->assertEquals($client->id, $response->json('data.client_id'));
        $this->assertEquals('SIAKAD Mobile App', $response->json('data.app_name'));
        $this->assertEquals(1000000.0, $response->json('data.grand_total'));

        // Client count remains 1, project count is 1
        $this->assertEquals(1, Client::count());
        $this->assertEquals(1, ClientProject::count());
    }

    public function test_creation_validation_requires_mandatory_fields(): void
    {
        $admin = $this->createAdminUser();

        // Missing required mode and app_name
        $response = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/clients', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mode', 'app_name', 'project_type']);
    }

    public function test_non_admin_cannot_access_client_projects(): void
    {
        $staff = $this->createRegularUser();

        $response = $this->actingAs($staff, 'web')->getJson('/api/v1/admin/clients');
        $response->assertStatus(403);

        $postRes = $this->actingAs($staff, 'web')->postJson('/api/v1/admin/clients', [
            'mode' => 'new_client',
            'name' => 'Test Company',
            'app_name' => 'Test App',
            'project_type' => 'development',
        ]);
        $postRes->assertStatus(403);
    }

    public function test_guest_cannot_access_client_projects(): void
    {
        $response = $this->getJson('/api/v1/admin/clients');
        $response->assertStatus(401);
    }

    public function test_admin_can_update_client_project_and_status(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create(['name' => 'PT Garuda Tekno']);
        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'ERP V1',
            'app_name' => 'ERP V1',
            'project_type' => 'development',
            'status' => 'Pending',
            'grand_total' => 2000000,
        ]);

        $updatePayload = [
            'app_name' => 'ERP V2 Enterprise',
            'status' => 'Aktif',
            'paid_amount' => 1000000,
            'client_name' => 'PT Garuda Tekno Prima',
            'roles' => ['Super Admin', 'Staff Gudang'],
        ];

        $response = $this->actingAs($admin, 'web')->putJson("/api/v1/admin/clients/{$project->id}", $updatePayload);

        $response->assertStatus(200);
        $this->assertEquals('ERP V2 Enterprise', $response->json('data.app_name'));
        $this->assertEquals('Aktif', $response->json('data.status'));
        $this->assertEquals('PT Garuda Tekno Prima', $response->json('data.client.name'));
        $this->assertCount(2, $response->json('data.roles'));
    }

    public function test_admin_can_delete_client_project_and_cascades_roles(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create(['name' => 'PT Buana Solusi']);
        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'HRIS App',
            'app_name' => 'HRIS App',
            'project_type' => 'development',
        ]);

        ProjectRole::create([
            'project_id' => $project->id,
            'role_name' => 'Admin HR',
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson("/api/v1/admin/clients/{$project->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('client_projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_roles', ['role_name' => 'Admin HR']);
        // Client without projects is also cleanly removed
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_admin_cannot_delete_client_project_with_existing_payment(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create(['name' => 'PT Surya Makmur']);
        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Finance Core',
            'app_name' => 'Finance Core',
            'project_type' => 'development',
            'grand_total' => 10000000.00,
            'paid_amount' => 5000000.00,
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson("/api/v1/admin/clients/{$project->id}");

        $response->assertStatus(422)
            ->assertJsonPath('title', 'Proyek Tidak Dapat Dihapus')
            ->assertJsonPath('detail', 'Proyek yang sudah memiliki riwayat pembayaran tidak dapat dihapus.');

        $this->assertDatabaseHas('client_projects', ['id' => $project->id]);
    }

    public function test_admin_can_export_client_projects_to_csv_with_utf8_bom(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create([
            'name' => '=HYPERLINK("http://attacker.com")', // Test CWE-1236 injection mitigation
            'industry' => 'Finance',
        ]);

        ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Secure App',
            'app_name' => 'Secure App',
            'project_type' => 'development',
            'status' => 'Aktif',
            'grand_total' => 5000000,
            'paid_amount' => 2500000,
        ]);

        $response = $this->actingAs($admin, 'web')->get('/api/v1/admin/clients/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Check UTF-8 BOM is present at the beginning (\xEF\xBB\xBF)
        $this->assertTrue(str_starts_with($content, "\xEF\xBB\xBF"));

        // Check CWE-1236 mitigation prefixed with single quote
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString('Secure App', $content);
    }
}
