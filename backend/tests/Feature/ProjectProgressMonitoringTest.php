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

class ProjectProgressMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private Client $client;
    private ClientProject $project;
    private ProjectRole $role;
    private ProjectMenu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => ['admin'],
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role' => ['user'],
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'PT Nusantara Digital Prima',
            'industry' => 'Keuangan & ERP',
            'contact_person' => 'Bpk. Hendra',
            'phone' => '0812-3456-7890',
            'address' => 'Jl. Sudirman No. 45, Jakarta',
        ]);

        $this->project = ClientProject::create([
            'client_id' => $this->client->id,
            'app_name' => 'Sistem ERP Multi-Cabang',
            'project_name' => 'Sistem ERP Multi-Cabang',
            'project_type' => 'development',
            'lead_pic' => 'Muhammad Iqbal Murtadho',
            'use_landing' => true,
            'use_login' => true,
            'use_register' => false,
            'use_app_core' => true,
            'landing_price' => 2500000,
            'login_price' => 1000000,
            'register_price' => 0,
            'status' => 'Aktif',
            'grand_total' => 3500000,
            'paid_amount' => 0,
        ]);

        $this->role = ProjectRole::create([
            'project_id' => $this->project->id,
            'role_name' => 'Admin Utama',
            'sort_order' => 1,
        ]);

        $this->menu = ProjectMenu::create([
            'project_role_id' => $this->role->id,
            'menu_name' => 'Dashboard Keuangan & Rekap',
            'menu_desc' => 'Ringkasan arus kas dan laba rugi bulanan',
            'pic' => 'Muhammad Iqbal Murtadho',
            'price' => 750000,
            'is_done' => false,
            'has_create' => true,
            'has_read' => true,
            'has_update' => true,
            'has_delete' => true,
            'has_grafik' => true,
            'has_box_dashboard' => true,
            'has_import' => false,
            'has_export' => true,
            'has_filter' => true,
            'has_print' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_view_project_progress_metrics_and_menus(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/api/v1/admin/clients');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'app_name',
                    'project_type',
                    'status',
                    'roles' => [
                        '*' => [
                            'id',
                            'role_name',
                            'menus' => [
                                '*' => [
                                    'id',
                                    'menu_name',
                                    'is_done',
                                    'completed_date',
                                    'pic',
                                    'has_create',
                                    'has_read',
                                    'has_update',
                                    'has_delete',
                                    'has_grafik',
                                    'has_export',
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'meta'
        ]);

        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Dashboard Keuangan & Rekap', $response->json('data.0.roles.0.menus.0.menu_name'));
        $this->assertFalse((bool) $response->json('data.0.roles.0.menus.0.is_done'));
    }

    public function test_guest_and_unauthorized_user_cannot_toggle_menu_done(): void
    {
        $guestRes = $this->postJson("/api/v1/admin/menus/{$this->menu->id}/toggle-done", [
            'is_done' => true,
        ]);
        $guestRes->assertStatus(401);

        $staffRes = $this->actingAs($this->staff, 'web')
            ->postJson("/api/v1/admin/menus/{$this->menu->id}/toggle-done", [
                'is_done' => true,
            ]);
        $staffRes->assertStatus(403);
    }

    public function test_authorized_user_can_toggle_menu_completion_with_date_and_technician(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson("/api/v1/admin/menus/{$this->menu->id}/toggle-done", [
                'is_done' => true,
                'completed_date' => '2026-11-09',
                'pic' => 'Misbahudin',
            ]);

        $response->assertStatus(200);
        $this->assertTrue((bool) $response->json('data.is_done'));
        $this->assertEquals('2026-11-09', $response->json('data.completed_date'));
        $this->assertEquals('Misbahudin', $response->json('data.pic'));

        $this->assertDatabaseHas('project_menus', [
            'id' => $this->menu->id,
            'is_done' => true,
            'completed_date' => '2026-11-09',
            'pic' => 'Misbahudin',
        ]);
    }

    public function test_authorized_user_can_untoggle_menu_to_pending(): void
    {
        // First set to done
        $this->menu->update([
            'is_done' => true,
            'completed_date' => '2026-11-09',
            'pic' => 'Misbahudin',
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson("/api/v1/admin/menus/{$this->menu->id}/toggle-done", [
                'is_done' => false,
            ]);

        $response->assertStatus(200);
        $this->assertFalse((bool) $response->json('data.is_done'));
        $this->assertNull($response->json('data.completed_date'));

        $this->assertDatabaseHas('project_menus', [
            'id' => $this->menu->id,
            'is_done' => false,
            'completed_date' => null,
        ]);
    }
}
