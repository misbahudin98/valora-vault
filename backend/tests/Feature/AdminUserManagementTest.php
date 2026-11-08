<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('sanctum.stateful', ['valora-vault.test', 'hyperion-vault.test', 'template.org', 'localhost']);
        Config::set('session.driver', 'file');
    }

    public function test_admin_can_list_users_and_toggle_status_and_revoke_sessions(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@hyperion-vault.test',
            'role' => ['admin'],
            'is_active' => true,
            'token_version' => 1
        ]);

        $regularUser = User::factory()->create([
            'name' => 'Normal User',
            'email' => 'user@hyperion-vault.test',
            'role' => ['user'],
            'is_active' => true,
            'token_version' => 1
        ]);

        // 1. Regular user trying to access admin endpoints gets 403 Forbidden
        $unauthRes = $this->actingAs($regularUser)->getJson('/api/v1/admin/users', ['Referer' => 'http://hyperion-vault.test']);
        $unauthRes->assertStatus(403);

        // 2. Admin can list all users
        $listRes = $this->actingAs($admin)->getJson('/api/v1/admin/users', ['Referer' => 'http://hyperion-vault.test']);
        $listRes->assertStatus(200);
        $listRes->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'role', 'is_active', 'status', 'token_version']
            ],
            'meta' => ['total', 'active_count']
        ]);
        $this->assertEquals(2, $listRes->json('meta.total'));

        // 3. Admin can toggle regular user status to suspended
        $toggleRes = $this->actingAs($admin)->postJson("/api/v1/admin/users/{$regularUser->id}/toggle-status", [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $toggleRes->assertStatus(200);
        $this->assertFalse($regularUser->fresh()->is_active);
        $this->assertEquals('suspended', $regularUser->fresh()->status);

        // 4. Admin cannot toggle their own status
        $selfToggleRes = $this->actingAs($admin)->postJson("/api/v1/admin/users/{$admin->id}/toggle-status", [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $selfToggleRes->assertStatus(400);

        // 5. Admin can revoke sessions (increments token_version)
        $initialVersion = $regularUser->fresh()->token_version;
        $revokeRes = $this->actingAs($admin)->postJson("/api/v1/admin/users/{$regularUser->id}/revoke-sessions", [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $revokeRes->assertStatus(200);
        $this->assertEquals($initialVersion + 1, $regularUser->fresh()->token_version);

        // 6. Suspended user gets blocked by CheckUserActive middleware
        $suspendedAttempt = $this->actingAs($regularUser->fresh())->getJson('/api/v1/user/profile', [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $suspendedAttempt->assertStatus(403);
    }

    public function test_admin_can_create_update_and_delete_user(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Boss',
            'email' => 'boss@hyperion-vault.test',
            'role' => ['admin'],
            'is_active' => true,
            'token_version' => 1
        ]);

        // 1. Create (Store)
        $createRes = $this->actingAs($admin)->postJson('/api/v1/admin/users', [
            'name' => 'Admin Baru',
            'email' => 'admin_baru@valora-vault.test',
            'password' => 'secret12345',
            'role' => ['admin'],
            'is_active' => true,
        ], ['Referer' => 'http://valora-vault.test']);

        $createRes->assertStatus(201);
        $createRes->assertJsonPath('user.email', 'admin_baru@valora-vault.test');
        $this->assertDatabaseHas('users', ['email' => 'admin_baru@valora-vault.test']);
        $newUser = User::where('email', 'admin_baru@valora-vault.test')->first();
        $this->assertEquals(['admin'], $newUser->role);

        // 2. Update
        $updateRes = $this->actingAs($admin)->putJson("/api/v1/admin/users/{$newUser->id}", [
            'name' => 'Admin Senior',
            'email' => 'admin_senior@valora-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ], ['Referer' => 'http://valora-vault.test']);

        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'admin_senior@valora-vault.test']);
        $this->assertEquals('Admin Senior', $newUser->fresh()->name);

        // 3. Delete Self (Forbidden)
        $deleteSelfRes = $this->actingAs($admin)->deleteJson("/api/v1/admin/users/{$admin->id}", [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $deleteSelfRes->assertStatus(400);

        // 4. Delete Target User
        $deleteRes = $this->actingAs($admin)->deleteJson("/api/v1/admin/users/{$newUser->id}", [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_admin_can_manage_and_revoke_sessions(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin SecOps',
            'email' => 'secops@hyperion-vault.test',
            'role' => ['admin'],
            'is_active' => true,
            'token_version' => 1
        ]);

        $user1 = User::factory()->create(['name' => 'User One', 'role' => ['admin'], 'token_version' => 1]);
        $user2 = User::factory()->create(['name' => 'User Two', 'role' => ['admin'], 'token_version' => 1]);

        // Register active device sessions
        $deviceAdmin = \App\Models\UserDevice::create([
            'user_id' => $admin->id,
            'session_id' => 'sess_admin_123',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0',
            'device_name' => 'Chrome on Windows',
            'device_type' => 'desktop',
            'token_version' => 1,
            'last_active_at' => now(),
            'expires_at' => now()->addMinutes(120),
        ]);

        $deviceUser1 = \App\Models\UserDevice::create([
            'user_id' => $user1->id,
            'session_id' => 'sess_user1_456',
            'ip_address' => '192.168.1.50',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/604.1',
            'device_name' => 'Safari on iOS',
            'device_type' => 'mobile',
            'token_version' => 1,
            'last_active_at' => now(),
            'expires_at' => now()->addMinutes(120),
        ]);

        $deviceUser2 = \App\Models\UserDevice::create([
            'user_id' => $user2->id,
            'session_id' => 'sess_user2_789',
            'ip_address' => '10.0.0.12',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) Firefox/120.0',
            'device_name' => 'Firefox on Linux',
            'device_type' => 'desktop',
            'token_version' => 1,
            'last_active_at' => now(),
            'expires_at' => now()->addMinutes(120),
        ]);

        // 1. List active device sessions
        $res = $this->actingAs($admin)->getJson('/api/v1/admin/sessions', ['Referer' => 'http://hyperion-vault.test']);
        $res->assertStatus(200);
        $res->assertJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'user_name', 'user_email', 'user_role', 'ip_address', 'device_name', 'expires_at']
            ],
            'meta' => ['total_tracked', 'active_users', 'max_devices_cap']
        ]);
        $this->assertCount(3, $res->json('data'));

        // 2. Revoke a specific single device session
        $revokeDevice = $this->actingAs($admin)->postJson("/api/v1/admin/sessions/{$deviceUser1->id}/revoke", [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $revokeDevice->assertStatus(200);
        $this->assertDatabaseMissing('user_devices', ['id' => $deviceUser1->id]);

        // 3. Revoke all sessions for a user by user ID
        $revokeUser = $this->actingAs($admin)->postJson("/api/v1/admin/sessions/user/{$user2->id}/revoke", [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $revokeUser->assertStatus(200);
        $this->assertEquals(2, $user2->fresh()->token_version);
        $this->assertDatabaseMissing('user_devices', ['id' => $deviceUser2->id]);

        // 4. Auto-cleanup endpoint
        $cleanupRes = $this->actingAs($admin)->postJson('/api/v1/admin/sessions/cleanup', [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $cleanupRes->assertStatus(200);

        // 5. Emergency Revoke All (except admin)
        $revokeAll = $this->actingAs($admin)->postJson('/api/v1/admin/sessions/revoke-all', [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $revokeAll->assertStatus(200);
    }

    public function test_store_user_form_request_validations(): void
    {
        $admin = User::factory()->create([
            'role' => ['admin'],
            'is_active' => true,
        ]);

        // 1. Missing required fields triggers 422
        $res = $this->actingAs($admin)->postJson('/api/v1/admin/users', [], [
            'Referer' => 'http://hyperion-vault.test'
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name', 'email', 'password']);

        // 2. Invalid role triggers 422
        $invalidRoleRes = $this->actingAs($admin)->postJson('/api/v1/admin/users', [
            'name' => 'Invalid Role User',
            'email' => 'invalid_role@hyperion-vault.test',
            'password' => 'secret12345',
            'role' => ['non_existent_role_xyz'],
        ], ['Referer' => 'http://hyperion-vault.test']);

        $invalidRoleRes->assertStatus(422);
        $invalidRoleRes->assertJsonValidationErrors(['role']);

        // 3. Short password triggers 422
        $shortPassRes = $this->actingAs($admin)->postJson('/api/v1/admin/users', [
            'name' => 'Short Pass',
            'email' => 'short_pass@hyperion-vault.test',
            'password' => '123',
        ], ['Referer' => 'http://hyperion-vault.test']);

        $shortPassRes->assertStatus(422);
        $shortPassRes->assertJsonValidationErrors(['password']);
    }

    public function test_user_policy_granular_permissions(): void
    {
        $admin = User::factory()->create(['role' => ['admin'], 'is_active' => true]);
        $targetUser = User::factory()->create(['role' => ['user'], 'is_active' => true]);
        $policy = new \App\Policies\UserPolicy();

        // Admin holds wildcard permissions
        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $targetUser));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $targetUser));
        $this->assertTrue($policy->delete($admin, $targetUser));
        $this->assertTrue($policy->toggleStatus($admin, $targetUser));
        $this->assertTrue($policy->revokeSessions($admin, $targetUser));
        $this->assertTrue($policy->export($admin));

        // Regular user without admin or permissions
        $this->assertFalse($policy->viewAny($targetUser));
        $this->assertFalse($policy->create($targetUser));
        $this->assertFalse($policy->update($targetUser, $admin));
        $this->assertFalse($policy->delete($targetUser, $admin));
        $this->assertFalse($policy->toggleStatus($targetUser, $admin));
        $this->assertFalse($policy->revokeSessions($targetUser, $admin));
        $this->assertFalse($policy->export($targetUser));

        // Regular user viewing their own profile
        $this->assertTrue($policy->view($targetUser, $targetUser));
    }

    public function test_cleanup_session_request_validations(): void
    {
        $admin = User::factory()->create([
            'role' => ['admin'],
            'is_active' => true,
        ]);

        // 1. Negative grace_minutes triggers 422
        $res = $this->actingAs($admin)->postJson('/api/v1/admin/sessions/cleanup', [
            'grace_minutes' => -10,
        ], ['Referer' => 'http://hyperion-vault.test']);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['grace_minutes']);

        // 2. Exceeding max grace_minutes triggers 422
        $excessRes = $this->actingAs($admin)->postJson('/api/v1/admin/sessions/cleanup', [
            'grace_minutes' => 99999,
        ], ['Referer' => 'http://hyperion-vault.test']);
        $excessRes->assertStatus(422);
        $excessRes->assertJsonValidationErrors(['grace_minutes']);
    }

    public function test_session_policy_granular_permissions(): void
    {
        $admin = User::factory()->create(['role' => ['admin'], 'is_active' => true]);
        $regularUser = User::factory()->create(['role' => ['user'], 'is_active' => true]);
        $policy = new \App\Policies\SessionPolicy();

        // Admin has full permissions
        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->revoke($admin));
        $this->assertTrue($policy->cleanup($admin));

        // Regular user is denied
        $this->assertFalse($policy->viewAny($regularUser));
        $this->assertFalse($policy->revoke($regularUser));
        $this->assertFalse($policy->cleanup($regularUser));
    }
}
