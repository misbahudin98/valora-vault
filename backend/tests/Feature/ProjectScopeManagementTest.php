<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ProjectDatabaseTable;
use App\Models\ProjectMenu;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ProjectScopeManagementTest extends TestCase
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

    public function test_admin_can_add_role_and_menus_with_flexible_pic_and_10_capabilities(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create(['name' => 'PT Prima Mandiri']);
        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'ERP V1',
            'app_name' => 'ERP V1',
            'lead_pic' => 'Muhammad Iqbal Murtadho',
            'use_login' => true,
            'login_price' => 1000000,
        ]);

        // 1. Add Role
        $roleRes = $this->actingAs($admin, 'web')->postJson("/api/v1/admin/projects/{$project->id}/roles", [
            'role_name' => 'Manager Logistik',
        ]);
        $roleRes->assertStatus(201);
        $roleId = $roleRes->json('data.id');

        // 2. Add Menu with flexible PIC (Misbahudin) and 10 capability checkboxes
        $menuPayload = [
            'menu_name' => 'Tracking Surat Jalan Realtime',
            'menu_desc' => 'Pelacakan armada logistik dengan GPS & barcode',
            'pic' => 'Misbahudin',
            'price' => 850000,
            'has_create' => true,
            'has_read' => true,
            'has_update' => true,
            'has_delete' => false,
            'has_grafik' => true,
            'has_box_dashboard' => true,
            'has_import' => true,
            'has_export' => true,
            'has_filter' => true,
            'has_print' => true,
        ];

        $menuRes = $this->actingAs($admin, 'web')->postJson("/api/v1/admin/roles/{$roleId}/menus", $menuPayload);
        $menuRes->assertStatus(201);
        $menuId = $menuRes->json('data.id');

        $this->assertEquals('Misbahudin', $menuRes->json('data.pic'));
        $this->assertTrue($menuRes->json('data.has_grafik'));
        $this->assertTrue($menuRes->json('data.has_print'));
        $this->assertFalse($menuRes->json('data.has_delete'));

        // Check project grand total updated (1,000,000 login + 850,000 menu = 1,850,000)
        $project->refresh();
        $this->assertEquals(1850000, (int) $project->grand_total);

        // 3. Update Menu (change PIC to Muhammad Iqbal and toggle checkbox)
        $updateRes = $this->actingAs($admin, 'web')->putJson("/api/v1/admin/menus/{$menuId}", [
            'pic' => 'Muhammad Iqbal Murtadho',
            'has_delete' => true,
            'price' => 900000,
        ]);
        $updateRes->assertStatus(200);
        $this->assertEquals('Muhammad Iqbal Murtadho', $updateRes->json('data.pic'));
        $this->assertTrue($updateRes->json('data.has_delete'));

        // Check grand total updated with new menu price
        $project->refresh();
        $this->assertEquals(1900000, (int) $project->grand_total);

        // 4. Toggle Menu Completion
        $toggleRes = $this->actingAs($admin, 'web')->postJson("/api/v1/admin/menus/{$menuId}/toggle-done", [
            'is_done' => true,
            'completed_date' => '2026-11-09',
            'pic' => 'Misbahudin',
        ]);
        $toggleRes->assertStatus(200);
        $this->assertTrue($toggleRes->json('data.is_done'));
        $this->assertEquals('Misbahudin', $toggleRes->json('data.pic'));
    }

    public function test_admin_can_manage_database_tables(): void
    {
        $admin = $this->createAdminUser();

        $client = Client::create(['name' => 'PT Test']);
        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'App',
            'app_name' => 'App',
            'grand_total' => 0,
        ]);

        // Add Database Table
        $res = $this->actingAs($admin, 'web')->postJson("/api/v1/admin/projects/{$project->id}/database-tables", [
            'table_name' => 'inventory_items',
            'price' => 100000,
        ]);
        $res->assertStatus(201);
        $tableId = $res->json('data.id');

        $project->refresh();
        $this->assertEquals(100000, (int) $project->grand_total);

        // Toggle Table Done
        $toggleRes = $this->actingAs($admin, 'web')->postJson("/api/v1/admin/database-tables/{$tableId}/toggle-done", [
            'is_done' => true,
        ]);
        $toggleRes->assertStatus(200);
        $this->assertTrue($toggleRes->json('data.is_done'));

        // Delete Table
        $delRes = $this->actingAs($admin, 'web')->deleteJson("/api/v1/admin/database-tables/{$tableId}");
        $delRes->assertStatus(200);

        $project->refresh();
        $this->assertEquals(0, (int) $project->grand_total);
    }
}
