<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_are_stored_and_retrieved_without_consolidation_mutation(): void
    {
        $user = User::factory()->create([
            'role' => ['manager', 'staff'],
        ]);

        $this->assertEquals(['manager', 'staff'], $user->role);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertTrue($user->hasRole('staff'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_has_role_is_strictly_explicit_without_alias_bleed(): void
    {
        $user = User::factory()->create([
            'role' => ['manager'],
        ]);

        $this->assertTrue($user->hasRole('manager'));
        $this->assertFalse($user->hasRole('supervisor'));
        $this->assertFalse($user->hasRole('team_lead'));
        $this->assertFalse($user->hasRole('auditor'));
    }

    public function test_get_permissions_resolves_exact_role_permissions_directly(): void
    {
        config([
            'permissions.roles' => [
                'manager' => ['reports.view', 'reports.export'],
                'staff' => ['profile.view'],
            ],
        ]);

        $user = User::factory()->create([
            'role' => ['manager'],
        ]);

        $perms = $user->getPermissions();
        $this->assertContains('reports.view', $perms);
        $this->assertContains('reports.export', $perms);
        $this->assertNotContains('profile.view', $perms);
    }

    public function test_absence_of_role_consolidation_constants_and_methods(): void
    {
        $userClass = new \ReflectionClass(User::class);
        $this->assertFalse($userClass->hasConstant('ROLE_CONSOLIDATION_MAP'), 'ROLE_CONSOLIDATION_MAP must not exist in User');
        $this->assertFalse($userClass->hasConstant('ROLE_ALIASES'), 'ROLE_ALIASES must not exist in User');
        $this->assertFalse($userClass->hasMethod('normalizeRoles'), 'normalizeRoles mutator must not exist in User');
        $this->assertFalse($userClass->hasMethod('expandRoleAliases'), 'expandRoleAliases must not exist in User');

        $this->assertArrayNotHasKey('consolidation_map', config('permissions'), 'consolidation_map must not exist in config/permissions.php');
        $this->assertArrayNotHasKey('aliases', config('permissions'), 'aliases must not exist in config/permissions.php');
    }

    public function test_user_with_unregistered_or_invalid_role_is_strictly_rejected_from_session_access(): void
    {
        $user = User::factory()->create([
            'role' => ['forced_unregistered_role'],
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // 1. Invariant: Hak akses kosong
        $this->assertEquals([], $user->getPermissions());
        $this->assertFalse($user->isAdmin());

        // 2. Login ditolak langsung tanpa akses sesi (Instruksi 6 & 8)
        $loginRes = $this->withHeader('X-XSRF-TOKEN', 'test-token')
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password123',
            ]);
        $loginRes->assertStatus(422);

        // 3. Pesan error dilarang keras expose list peran (Instruksi 7)
        $errorMsg = $loginRes->json('errors.email.0') ?? $loginRes->json('message') ?? '';
        $this->assertNotEmpty($errorMsg);
        foreach (array_keys(config('permissions.roles')) as $role) {
            $this->assertStringNotContainsString($role, $errorMsg, "Error message must never expose role '{$role}'");
        }

        // 4. Session middleware (CheckUserActive) langsung menolak dengan 403 jika role tidak sesuai permission.php
        $meRes = $this->actingAs($user)->getJson('/api/v1/user/profile');
        $meRes->assertStatus(403);
        $this->assertStringNotContainsString('admin', $meRes->getContent());
    }

    public function test_assigning_unregistered_role_in_admin_controller_is_immediately_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => ['admin'],
            'is_active' => true,
        ]);

        // 1. Store ditolak saat role tidak terdaftar di permission.php
        $storeRes = $this->actingAs($admin)->postJson('/api/v1/admin/users', [
            'name' => 'Invalid Role User',
            'email' => 'invalid_role@valora-vault.test',
            'password' => 'password123',
            'role' => ['invalid_fake_role'],
            'is_active' => true,
        ]);
        $storeRes->assertStatus(422);
        $storeRes->assertJsonValidationErrors(['role']);

        // Pesan error tidak membocorkan list peran
        $storeErrorMsg = $storeRes->json('errors.role.0') ?? '';
        foreach (array_keys(config('permissions.roles')) as $role) {
            $this->assertStringNotContainsString($role, $storeErrorMsg);
        }

        // 2. Update ditolak saat role tidak terdaftar di permission.php
        $target = User::factory()->create([
            'role' => ['user'],
            'is_active' => true,
        ]);

        $updateRes = $this->actingAs($admin)->putJson("/api/v1/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => ['unregistered_role_canonical'],
            'is_active' => true,
        ]);
        $updateRes->assertStatus(422);
        $updateRes->assertJsonValidationErrors(['role']);

        $updateErrorMsg = $updateRes->json('errors.role.0') ?? '';
        foreach (array_keys(config('permissions.roles')) as $role) {
            $this->assertStringNotContainsString($role, $updateErrorMsg);
        }
    }
}
