<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ProjectMenu;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserExtendedProfileAndProjectWorkersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $developer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@valora-vault.test',
            'password' => 'password123',
            'role' => ['admin'],
            'is_active' => true,
            'token_version' => 1,
            'jabatan' => 'Lead System Architect',
            'phone' => '081234567890',
            'address' => 'Jakarta Selatan',
            'github_url' => 'https://github.com/valora-admin',
            'linkedin_url' => 'https://linkedin.com/in/valora-admin',
            'skills' => ['Laravel', 'Svelte 5', 'Security Hardening'],
            'allowed_features' => ['*'],
        ]);

        $this->developer = User::create([
            'name' => 'Muhammad Iqbal Murtadho',
            'email' => 'iqbal@valora-vault.test',
            'password' => 'password123',
            'role' => ['admin'],
            'is_active' => true,
            'token_version' => 1,
            'jabatan' => 'Lead Fullstack Engineer',
            'phone' => '081388991122',
            'address' => 'Surabaya',
            'github_url' => 'https://github.com/iqbalmurtadho',
            'linkedin_url' => 'https://linkedin.com/in/iqbalmurtadho',
            'skills' => ['Laravel', 'Svelte 5', 'Tailwind CSS', 'PostgreSQL'],
            'allowed_features' => ['dashboard', 'admin-clients', 'admin-progres', 'admin-tagihan'],
        ]);
    }

    public function test_admin_can_create_user_with_extended_profile_and_multiple_skills_and_allowed_features(): void
    {
        $payload = [
            'name' => 'Misbahudin',
            'email' => 'misbah@valora-vault.test',
            'password' => 'password123',
            'role' => ['admin'],
            'is_active' => true,
            'jabatan' => 'Senior Backend and Database Architect',
            'phone' => '081977665544',
            'address' => 'Jakarta Selatan, DKI Jakarta',
            'github_url' => 'https://github.com/misbahudin',
            'linkedin_url' => 'https://linkedin.com/in/misbahudin',
            'skills' => ['PHP 8.4', 'Database Architecture', 'Docker', 'Linux'],
            'allowed_features' => ['dashboard', 'admin-clients', 'admin-progres', 'admin-tagihan', 'admin-system'],
        ];

        $res = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('user.name', 'Misbahudin')
            ->assertJsonPath('user.jabatan', 'Senior Backend and Database Architect')
            ->assertJsonPath('user.phone', '081977665544')
            ->assertJsonPath('user.skills.0', 'PHP 8.4')
            ->assertJsonPath('user.allowed_features.1', 'admin-clients');

        $this->assertDatabaseHas('users', [
            'email' => 'misbah@valora-vault.test',
            'jabatan' => 'Senior Backend and Database Architect',
            'phone' => '081977665544',
        ]);
    }

    public function test_admin_can_update_user_extended_profile_and_skills(): void
    {
        $payload = [
            'name' => 'Muhammad Iqbal M., S.Kom.',
            'email' => 'iqbal@valora-vault.test',
            'jabatan' => 'Principal Fullstack Engineer',
            'phone' => '081399990000',
            'skills' => ['Laravel 13', 'Svelte 5 Runes', 'PostgreSQL', 'Microservices'],
            'allowed_features' => ['*'],
        ];

        $res = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/users/{$this->developer->id}", $payload);

        $res->assertStatus(200)
            ->assertJsonPath('user.name', 'Muhammad Iqbal M., S.Kom.')
            ->assertJsonPath('user.jabatan', 'Principal Fullstack Engineer')
            ->assertJsonPath('user.phone', '081399990000');

        $this->developer->refresh();
        $this->assertEquals('Principal Fullstack Engineer', $this->developer->jabatan);
        $this->assertEquals(['Laravel 13', 'Svelte 5 Runes', 'PostgreSQL', 'Microservices'], $this->developer->skills);
        $this->assertEquals(['*'], $this->developer->allowed_features);
    }

    public function test_user_can_view_feature_helper(): void
    {
        // Admin with ['*'] can view all features
        $this->assertTrue($this->admin->canViewFeature('admin-system'));
        $this->assertTrue($this->admin->canViewFeature('admin-unknown-feature'));

        // Developer with restricted allowed_features
        $this->developer->update([
            'role' => ['manager'], // not admin
            'allowed_features' => ['dashboard', 'admin-clients', 'admin-progres'],
        ]);

        $this->assertTrue($this->developer->canViewFeature('dashboard'));
        $this->assertTrue($this->developer->canViewFeature('admin-clients'));
        $this->assertFalse($this->developer->canViewFeature('admin-system'));
    }

    public function test_client_project_creation_with_lead_pic_id_and_worker_ids(): void
    {
        $payload = [
            'mode' => 'new_client',
            'name' => 'PT Mandiri Mega Jaya',
            'contact_person' => 'Budi Santoso',
            'phone' => '081299887766',
            'email' => 'budi@mandirimega.id',
            'app_name' => 'Aplikasi Logistik Mega',
            'project_name' => 'Aplikasi Logistik Mega',
            'project_type' => 'development',
            'lead_pic_id' => $this->developer->id,
            'worker_ids' => [$this->admin->id, $this->developer->id],
            'roles' => ['Super Admin', 'Staff Gudang'],
        ];

        $res = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/clients', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('data.app_name', 'Aplikasi Logistik Mega')
            ->assertJsonPath('data.lead_pic_id', $this->developer->id)
            ->assertJsonPath('data.lead_pic', 'Muhammad Iqbal Murtadho')
            ->assertJsonCount(2, 'data.workers');

        $project = ClientProject::where('app_name', 'Aplikasi Logistik Mega')->first();
        $this->assertNotNull($project);
        $this->assertEquals($this->developer->id, $project->lead_pic_id);
        $this->assertEquals('Muhammad Iqbal Murtadho', $project->lead_pic);
        $this->assertCount(2, $project->workers);
    }

    public function test_client_project_update_syncs_workers_and_updates_lead_pic(): void
    {
        $client = Client::create([
            'name' => 'PT Surya Cipta',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Portal Surya',
            'app_name' => 'Portal Surya',
            'project_type' => 'development',
            'lead_pic_id' => $this->admin->id,
            'lead_pic' => $this->admin->name,
            'status' => 'Aktif',
        ]);

        $project->workers()->sync([$this->admin->id => ['role_in_project' => 'Lead PIC']]);

        // Update to assign Iqbal as lead PIC and both as workers
        $updatePayload = [
            'app_name' => 'Portal Surya Reborn',
            'project_type' => 'development',
            'status' => 'Aktif',
            'lead_pic_id' => $this->developer->id,
            'worker_ids' => [$this->admin->id, $this->developer->id],
        ];

        $res = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/clients/{$project->id}", $updatePayload);

        $res->assertStatus(200)
            ->assertJsonPath('data.lead_pic_id', $this->developer->id)
            ->assertJsonPath('data.lead_pic', 'Muhammad Iqbal Murtadho')
            ->assertJsonCount(2, 'data.workers');

        $project->refresh();
        $this->assertEquals($this->developer->id, $project->lead_pic_id);
        $this->assertEquals('Muhammad Iqbal Murtadho', $project->lead_pic);
        $this->assertCount(2, $project->workers);
    }

    public function test_scope_controller_add_menu_assigns_pic_user_id(): void
    {
        $client = Client::create(['name' => 'Klien Test']);
        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Sistem Klien',
            'app_name' => 'Sistem Klien',
            'lead_pic_id' => $this->developer->id,
            'lead_pic' => $this->developer->name,
        ]);
        $role = ProjectRole::create([
            'project_id' => $project->id,
            'role_name' => 'Administrator',
            'sort_order' => 1,
        ]);

        $res = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/roles/{$role->id}/menus", [
                'menu_name' => 'Manajemen Pengguna Staf',
                'menu_desc' => 'Kelola akun staf dan hak akses',
                'pic_user_id' => $this->developer->id,
                'price' => 750000,
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.menu_name', 'Manajemen Pengguna Staf')
            ->assertJsonPath('data.pic_user_id', $this->developer->id)
            ->assertJsonPath('data.pic', 'Muhammad Iqbal Murtadho');

        $menu = ProjectMenu::where('menu_name', 'Manajemen Pengguna Staf')->first();
        $this->assertNotNull($menu);
        $this->assertEquals($this->developer->id, $menu->pic_user_id);
        $this->assertEquals('Muhammad Iqbal Murtadho', $menu->pic);
    }

    public function test_client_project_index_and_show_eager_loads_lead_pic_user_and_workers(): void
    {
        $client = Client::create(['name' => 'PT Mitra']);
        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Mitra App',
            'app_name' => 'Mitra App',
            'lead_pic_id' => $this->developer->id,
            'lead_pic' => $this->developer->name,
        ]);
        $project->workers()->sync([$this->developer->id => ['role_in_project' => 'Lead PIC']]);

        // Index
        $indexRes = $this->actingAs($this->admin)->getJson('/api/v1/admin/clients');
        $indexRes->assertStatus(200)
            ->assertJsonPath('data.0.lead_pic_user.id', $this->developer->id)
            ->assertJsonPath('data.0.workers.0.id', $this->developer->id);

        // Show
        $showRes = $this->actingAs($this->admin)->getJson("/api/v1/admin/clients/{$project->id}");
        $showRes->assertStatus(200)
            ->assertJsonPath('data.lead_pic_user.id', $this->developer->id)
            ->assertJsonPath('data.workers.0.id', $this->developer->id);
    }
}
