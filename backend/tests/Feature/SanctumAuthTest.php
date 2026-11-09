<?php

declare(strict_types=1);

namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SanctumAuthTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        Config::set('sanctum.stateful', ['valora-vault.test', 'hyperion-vault.test', 'template.org', 'localhost']);
        Config::set('session.driver', 'file');
    }

    protected function tearDown(): void {
        \Illuminate\Support\Facades\Artisan::call('up');
        parent::tearDown();
    }

    public function test_complete_sanctum_lifecycle_multiple_roles_and_signed_jwt_cookie(): void {
        // 1. User dengan role admin dalam database
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@valora-vault.test',
            'password' => 'password123',
            'role' => ['admin']
        ]);

        // Verifikasi model helpers
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('guest'));
        $this->assertTrue($user->hasAllRoles('admin'));
        $this->assertFalse($user->hasAllRoles('admin', 'guest'));

        // 2. Ambil Cookie CSRF (Wajib Return HTTP 204)
        $csrfRes = $this->get('/sanctum/csrf-cookie', ['Referer' => 'http://valora-vault.test']);
        $csrfRes->assertStatus(204);
        $csrfRes->assertCookie('XSRF-TOKEN');
        $csrfRes->assertCookie(config('session.cookie'));

        $xsrfToken = $csrfRes->getCookie('XSRF-TOKEN')->getValue();
        $sessionCookie = $csrfRes->getCookie(config('session.cookie'))->getValue();

        // 3. Mutasi tanpa CSRF Header WAJIB HTTP 419
        $noCsrfRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->postJson('/api/v1/auth/login', [
            'email' => 'admin@valora-vault.test',
            'password' => 'password123'
        ], [
            'Referer' => 'http://valora-vault.test',
        ]);
        $noCsrfRes->assertStatus(419);

        // 4. Login Sukses dengan Role Admin & Signed JWT Cookie
        DB::enableQueryLog();
        $loginRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/login', [
            'email' => 'admin@valora-vault.test',
            'password' => 'password123'
        ], [
            'Referer' => 'http://valora-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);

        $loginRes->assertStatus(200);
        $this->assertAuthenticatedAs($user);

        // Verifikasi Cookie Role & Signed JWT Cookie
        $loginRes->assertPlainCookie('user_role', 'admin');
        $loginRes->assertPlainCookie('auth_jwt');

        $rawJwt = collect($loginRes->headers->getCookies())->first(fn ($c) => $c->getName() === 'auth_jwt');
        $this->assertNotNull($rawJwt);
        $jwtValue = $rawJwt->getValue();
        $verifiedClaims = JwtService::verifyToken($jwtValue);
        $this->assertNotNull($verifiedClaims, 'JWT Cookie gagal diverifikasi dengan signature APP_KEY.');
        $this->assertEquals(['admin'], $verifiedClaims['roles']);
        $this->assertEquals('Super Admin', $verifiedClaims['name']);
        $this->assertGreaterThan(time(), $verifiedClaims['exp']);

        // Verifikasi Anti-Tampering: Jika cookie diubah, verifikasi wajib gagal
        $tamperedJwt = $jwtValue . 'tampered';
        $this->assertNull(JwtService::verifyToken($tamperedJwt), 'Tampered token tidak boleh lolos verifikasi.');

        // 5. Verifikasi Mutlak: Zero Query ke Tabel `sessions` di Database
        $queries = DB::getQueryLog();
        foreach ($queries as $q) {
            $this->assertStringNotContainsString('sessions', $q['query'], 'Database session leakage terdeteksi!');
        }

        // 6. Akses Protected Endpoint & Role Authorization
        $meRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->getJson('/api/v1/auth/me', ['Referer' => 'http://valora-vault.test']);
        $meRes->assertStatus(200);
        $meRes->assertJsonPath('data.email', 'admin@valora-vault.test');
        $meRes->assertJsonPath('data.role', ['admin']);

        $adminRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->getJson('/api/v1/admin/stats', ['Referer' => 'http://valora-vault.test']);
        $adminRes->assertStatus(200);

        // 7. Logout & Cookie Cleanup
        $logoutRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/logout', [], [
            'Referer' => 'http://valora-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $logoutRes->assertStatus(204);

        $logoutJwt = collect($logoutRes->headers->getCookies())->first(fn ($c) => $c->getName() === 'auth_jwt');
        $this->assertNotNull($logoutJwt);
        $this->assertLessThan(time(), $logoutJwt->getExpiresTime());

        $logoutRole = collect($logoutRes->headers->getCookies())->first(fn ($c) => $c->getName() === 'user_role');
        $this->assertNotNull($logoutRole);
        $this->assertLessThan(time(), $logoutRole->getExpiresTime());

        // 8. Audit HTTP 401 Akses Unauthenticated
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $afterLogout = $this->withSession([])->getJson('/api/v1/auth/me', ['Referer' => 'http://hyperion-vault.test']);
        $afterLogout->assertStatus(401);
    }

    public function test_customizable_rbac_permission_matrix_and_version_endpoint(): void {
        $admin = User::factory()->create([
            'email' => 'admin_rbac@valora-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);

        // Verifikasi permissions inheritance untuk admin (wildcard)
        $this->assertTrue($admin->hasPermission('users.delete'));
        $this->assertTrue($admin->hasPermission('reports.export'));
        $this->assertEquals(['*'], $admin->getPermissions());

        // Verifikasi unconfigured roles yield empty permissions
        $guest = User::factory()->create([
            'email' => 'guest_rbac@valora-vault.test',
            'role' => ['guest'],
            'is_active' => true,
        ]);
        $this->assertFalse($guest->hasPermission('users.create'));
        $this->assertEmpty($guest->getPermissions());

        // Verifikasi endpoint /api/v1/admin/version
        $versionRes = $this->actingAs($admin)->getJson('/api/v1/admin/version', ['Referer' => 'http://valora-vault.test']);
        $versionRes->assertStatus(200);
        $versionRes->assertJsonStructure([
            'codebase_version',
            'installed_version',
            'is_up_to_date',
            'history'
        ]);
        $expectedVersion = json_decode(file_get_contents(base_path('composer.json')), true)['version'] ?? '6.31.0';
        $this->assertEquals($expectedVersion, $versionRes->json('codebase_version'));
    }

    public function test_auto_remove_revoked_session_and_cookie_purge(): void {
        $user = User::factory()->create([
            'email' => 'revoked@hyperion-vault.test',
            'password' => 'password123',
            'role' => ['admin'],
            'token_version' => 1,
            'is_active' => true,
        ]);

        $csrfRes = $this->get('/sanctum/csrf-cookie', ['Referer' => 'http://hyperion-vault.test']);
        $xsrfToken = $csrfRes->getCookie('XSRF-TOKEN')->getValue();
        $sessionCookie = $csrfRes->getCookie(config('session.cookie'))->getValue();

        $loginRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/login', [
            'email' => 'revoked@hyperion-vault.test',
            'password' => 'password123'
        ], [
            'Referer' => 'http://hyperion-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $loginRes->assertStatus(200);

        // Verify device was created in user_devices
        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id]);

        // Now revoke user sessions by incrementing token_version
        $user->increment('token_version');

        // Next request with the old session must return 401 with revocation message and forgotten cookies
        $checkRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken, 'auth_token_version' => 1])->getJson('/api/v1/auth/me', [
            'Referer' => 'http://hyperion-vault.test'
        ]);

        $checkRes->assertStatus(401);
        $this->assertStringContainsString('dicabut', $checkRes->json('message'));
        $checkRes->assertCookieExpired('auth_jwt');
        $checkRes->assertCookieExpired('user_role');
    }

    public function test_session_file_accumulation_prune_and_cleanup_endpoint(): void {
        $sessionDir = storage_path('framework/sessions');
        $this->assertTrue(is_dir($sessionDir));

        // 1. Create a dummy stale orphan session file (mtime 2 hours ago)
        $orphanFile = $sessionDir . DIRECTORY_SEPARATOR . 'test_orphan_dummy_session_123';
        file_put_contents($orphanFile, 'dummy_orphan_data');
        touch($orphanFile, time() - 7200);

        // 2. Create a dummy active session tracked in user_devices
        $admin = User::factory()->create([
            'email' => 'admin_session_test@hyperion-vault.test',
            'role' => ['admin'],
        ]);
        $activeSessionId = 'test_active_dummy_session_456';
        \App\Models\UserDevice::create([
            'user_id' => $admin->id,
            'session_id' => $activeSessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'device_name' => 'Chrome on Windows',
            'token_version' => 1,
            'last_active_at' => now(),
            'expires_at' => now()->addMinutes(120),
        ]);
        $activeFile = $sessionDir . DIRECTORY_SEPARATOR . $activeSessionId;
        file_put_contents($activeFile, 'dummy_active_data');
        touch($activeFile, time());

        // 3. Run disk session pruning with grace = 30 minutes
        $stats = \App\Models\UserDevice::pruneDiskSessions(graceMinutes: 30);
        $this->assertGreaterThanOrEqual(1, $stats['deleted']);
        $this->assertFalse(file_exists($orphanFile), 'Orphan file must be deleted by garbage collector.');
        $this->assertTrue(file_exists($activeFile), 'Active session file must be retained.');

        // 4. Test Artisan command execution
        $exitCode = \Illuminate\Support\Facades\Artisan::call('session:prune-files', ['--grace' => 60]);
        $this->assertEquals(0, $exitCode);

        // 5. Test Admin Cleanup Endpoint
        $cleanupRes = $this->actingAs($admin)->postJson('/api/v1/admin/sessions/cleanup', [
            'force' => false,
            'grace_minutes' => 60,
        ]);
        $cleanupRes->assertStatus(200);
        $cleanupRes->assertJsonStructure([
            'message',
            'purged_count',
            'disk_stats' => ['scanned', 'deleted', 'bytes_freed', 'active_retained'],
            'remaining_disk_files',
        ]);

        // Clean up test active file
        if (file_exists($activeFile)) {
            @unlink($activeFile);
        }
    }

    public function test_zero_session_file_duplication_on_login_and_logout(): void {
        $sessionDir = storage_path('framework/sessions');
        $this->assertTrue(is_dir($sessionDir));

        // 1. Clean slate
        \App\Models\UserDevice::truncate();
        \App\Models\UserDevice::pruneDiskSessions(graceMinutes: 0, force: true);
        $initialDiskCount = \App\Models\UserDevice::countDiskSessions();

        $user = User::factory()->create([
            'email' => 'zerodup@hyperion-vault.test',
            'password' => 'password123',
            'role' => ['admin'],
            'is_active' => true,
            'token_version' => 1,
        ]);

        // 2. Fetch CSRF Cookie
        $csrfRes = $this->get('/sanctum/csrf-cookie', ['Referer' => 'http://hyperion-vault.test']);
        $csrfRes->assertStatus(204);
        $xsrfToken = $csrfRes->getCookie('XSRF-TOKEN')->getValue();
        $preLoginSessionCookie = $csrfRes->getCookie(config('session.cookie'))->getValue();

        // Pre-login session file exists on disk
        $postCsrfCount = \App\Models\UserDevice::countDiskSessions();
        $this->assertEquals($initialDiskCount + 1, $postCsrfCount);

        // 3. Login with stateful credentials - must regenerate and purge old unauth session file
        $loginRes = $this->withCredentials()->withCookies([
            config('session.cookie') => $preLoginSessionCookie
        ])->postJson('/api/v1/auth/login', [
            'email' => 'zerodup@hyperion-vault.test',
            'password' => 'password123',
        ], [
            'Referer' => 'http://hyperion-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $loginRes->assertStatus(200);

        // Verify active device record was registered
        $device = \App\Models\UserDevice::where('user_id', $user->id)->first();
        $this->assertNotNull($device);
        $activeSessionId = $device->session_id;
        $this->assertNotEmpty($activeSessionId);

        // Active session file exists
        $activeSessionFile = $sessionDir . DIRECTORY_SEPARATOR . $activeSessionId;
        $this->assertTrue(file_exists($activeSessionFile));

        // Verify zero session duplication: disk session count did not increase by 2 (old was purged)
        $postLoginDiskCount = \App\Models\UserDevice::countDiskSessions();
        $this->assertEquals($initialDiskCount + 1, $postLoginDiskCount);

        // 4. Logout - must purge session file and not leave empty zombie file
        $authSessionCookie = $loginRes->getCookie(config('session.cookie'))->getValue();
        $newXsrfToken = $loginRes->getCookie('XSRF-TOKEN')?->getValue() ?: $xsrfToken;

        $logoutRes = $this->withCredentials()->withCookies([
            config('session.cookie') => $authSessionCookie
        ])->postJson('/api/v1/auth/logout', [], [
            'Referer' => 'http://hyperion-vault.test',
            'X-XSRF-TOKEN' => $newXsrfToken,
        ]);
        $logoutRes->assertStatus(204);

        // Terminate application kernel to trigger terminating callback hooks
        $this->app->terminate();

        // Verify device record was removed
        $this->assertDatabaseMissing('user_devices', ['id' => $device->id]);

        // Verify active session file was physically deleted
        $this->assertFalse(file_exists($activeSessionFile));

        // Verify disk sessions returned to initial count
        $postLogoutDiskCount = \App\Models\UserDevice::countDiskSessions();
        $this->assertLessThanOrEqual($initialDiskCount + 1, $postLogoutDiskCount);
    }

    public function test_admin_can_export_users_streamed_csv(): void {
        $admin = User::factory()->create([
            'email' => 'exportadmin@hyperion-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);

        $regular = User::factory()->create([
            'email' => 'regular@hyperion-vault.test',
            'role' => ['user'],
            'is_active' => true,
        ]);

        // Regular user cannot export (403)
        $this->actingAs($regular)->get('/api/v1/admin/users/export')
            ->assertStatus(403);

        // Admin can export (200, streamed CSV)
        $res = $this->actingAs($admin)->get('/api/v1/admin/users/export');
        $res->assertStatus(200);
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="users_export_', $res->headers->get('Content-Disposition'));
        
        $content = $res->streamedContent();
        $this->assertStringContainsString('ID,Nama,Email,Jabatan,"Kontak (HP/WA)","Alamat Domisili","Spesialisasi Keahlian","Peran (Roles)","Status Akun","Token Version","Dibuat Pada"', $content);
        $this->assertStringContainsString('exportadmin@hyperion-vault.test', $content);
        $this->assertStringContainsString('regular@hyperion-vault.test', $content);
    }

    public function test_admin_system_status_and_maintenance_mode(): void {
        $admin = User::factory()->create([
            'email' => 'sysadmin@hyperion-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);

        $regular = User::factory()->create([
            'email' => 'sysregular@hyperion-vault.test',
            'role' => ['user'],
            'is_active' => true,
        ]);

        // Regular cannot access system status
        $this->actingAs($regular)->getJson('/api/v1/admin/system/status')
            ->assertStatus(403);

        // Admin gets system status
        $res = $this->actingAs($admin)->getJson('/api/v1/admin/system/status');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'maintenance' => ['is_active'],
                'server' => ['php_version', 'laravel_version'],
                'logs' => ['laravel_log_size', 'client_log_size']
            ]);

        // Admin can toggle maintenance
        $enableRes = $this->actingAs($admin)->postJson('/api/v1/admin/system/maintenance/toggle', ['action' => 'enable']);
        $enableRes->assertStatus(200);
        $this->assertTrue($enableRes->json('is_active'));

        // Admin can disable maintenance
        $disableRes = $this->actingAs($admin)->postJson('/api/v1/admin/system/maintenance/toggle', ['action' => 'disable']);
        $disableRes->assertStatus(200);
        $this->assertFalse($disableRes->json('is_active'));

        // Admin can fetch logs
        $logsRes = $this->actingAs($admin)->getJson('/api/v1/admin/system/logs');
        $logsRes->assertStatus(200);
    }

    public function test_client_telemetry_error_recording(): void {
        $res = $this->postJson('/api/v1/telemetry/errors', [
            'error_id' => 'err-unit-test-123',
            'message' => 'TypeError: Cannot read properties of undefined',
            'stack' => 'at test.js:10:5',
            'url' => 'http://hyperion-vault.test/dashboard',
        ]);

        $res->assertStatus(200)
            ->assertJson(['status' => 'recorded', 'error_id' => 'err-unit-test-123']);

        $clientLog = storage_path('logs/client.log');
        $this->assertTrue(file_exists($clientLog));
        $this->assertStringContainsString('err-unit-test-123', file_get_contents($clientLog));
    }

    public function test_login_request_validations_and_account_status_checks(): void {
        $csrfRes = $this->get('/sanctum/csrf-cookie', ['Referer' => 'http://hyperion-vault.test']);
        $xsrfToken = $csrfRes->getCookie('XSRF-TOKEN')->getValue();
        $sessionCookie = $csrfRes->getCookie(config('session.cookie'))->getValue();

        // 1. Missing credentials returns 422
        $res = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/login', [], [
            'Referer' => 'http://hyperion-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email', 'password']);

        // 2. Short password returns 422
        $shortRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/login', [
            'email' => 'valid@hyperion-vault.test',
            'password' => 'short',
        ], [
            'Referer' => 'http://hyperion-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $shortRes->assertStatus(422);
        $shortRes->assertJsonValidationErrors(['password']);

        // 3. Suspended account login returns 422 with suspension notice
        $suspendedUser = User::factory()->create([
            'email' => 'suspended_user@hyperion-vault.test',
            'password' => 'password123',
            'role' => ['user'],
            'is_active' => false,
        ]);

        $suspendedRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/login', [
            'email' => 'suspended_user@hyperion-vault.test',
            'password' => 'password123',
        ], [
            'Referer' => 'http://hyperion-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $suspendedRes->assertStatus(422);
        $this->assertStringContainsString('ditangguhkan atau dinonaktifkan', $suspendedRes->json('errors.email.0'));

        // 4. Invalid unconfigured role returns 422
        $invalidRoleUser = User::factory()->create([
            'email' => 'invalid_role_user@hyperion-vault.test',
            'password' => 'password123',
            'role' => ['fictional_role_xyz'],
            'is_active' => true,
        ]);

        $invalidRoleRes = $this->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/login', [
            'email' => 'invalid_role_user@hyperion-vault.test',
            'password' => 'password123',
        ], [
            'Referer' => 'http://hyperion-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $invalidRoleRes->assertStatus(422);
        $this->assertStringContainsString('peran yang tidak valid', $invalidRoleRes->json('errors.email.0'));
    }

    public function test_logout_other_devices_invalidates_remote_sessions(): void {
        $user = User::factory()->create([
            'email' => 'multidevice@valora-vault.test',
            'password' => 'password123',
            'role' => ['admin'],
            'token_version' => 1,
            'is_active' => true,
        ]);

        $csrfRes = $this->get('/sanctum/csrf-cookie', ['Referer' => 'http://valora-vault.test']);
        $sessionCookie = $csrfRes->getCookie(config('session.cookie'))->getValue();
        $xsrfToken = $csrfRes->getCookie('XSRF-TOKEN')->getValue();

        // 1. User logs in from current device
        $loginRes = $this->withCredentials()->withCookies([
            config('session.cookie') => $sessionCookie
        ])->withSession(['_token' => $xsrfToken])->postJson('/api/v1/auth/login', [
            'email' => 'multidevice@valora-vault.test',
            'password' => 'password123',
        ], [
            'Referer' => 'http://valora-vault.test',
            'X-XSRF-TOKEN' => $xsrfToken,
        ]);
        $loginRes->assertStatus(200);

        $currentDevice = \App\Models\UserDevice::where('user_id', $user->id)->first();
        $this->assertNotNull($currentDevice);

        $authSessionCookie = $loginRes->getCookie(config('session.cookie'))->getValue();
        $newXsrfToken = $loginRes->getCookie('XSRF-TOKEN')?->getValue() ?: $xsrfToken;

        // 2. Simulate another device logged in on a remote phone
        $remoteDevice = \App\Models\UserDevice::create([
            'user_id' => $user->id,
            'session_id' => 'remote_sess_222',
            'ip_address' => '192.168.1.100',
            'device_name' => 'Safari on iPhone',
            'token_version' => 1,
            'last_active_at' => now(),
            'expires_at' => now()->addMinutes(120),
        ]);

        // 3. Current device requests logout of all other devices
        $res = $this->withCredentials()->withCookies([config('session.cookie') => $authSessionCookie])
            ->postJson('/api/v1/auth/logout-other-devices', [], [
                'Referer' => 'http://valora-vault.test',
                'X-XSRF-TOKEN' => $newXsrfToken,
            ]);

        $res->assertStatus(200);
        $this->assertEquals(2, $res->json('token_version'));
        $this->assertDatabaseMissing('user_devices', ['id' => $remoteDevice->id]);
        $this->assertDatabaseHas('user_devices', ['id' => $currentDevice->id]);
    }

    public function test_system_monitoring_form_request_validations(): void {
        $admin = User::factory()->create([
            'email' => 'sys_validator@hyperion-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);

        // 1. Invalid maintenance action triggers 422
        $res = $this->actingAs($admin)->postJson('/api/v1/admin/system/maintenance/toggle', [
            'action' => 'destroy_server',
        ], ['Referer' => 'http://hyperion-vault.test']);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['action']);

        // 2. Negative retry interval triggers 422
        $retryRes = $this->actingAs($admin)->postJson('/api/v1/admin/system/maintenance/toggle', [
            'action' => 'enable',
            'retry' => 2, // minimum is 5
        ], ['Referer' => 'http://hyperion-vault.test']);
        $retryRes->assertStatus(422);
        $retryRes->assertJsonValidationErrors(['retry']);

        // 3. Invalid clear logs type triggers 422
        $clearRes = $this->actingAs($admin)->deleteJson('/api/v1/admin/system/logs', [
            'type' => 'unknown_log_type',
        ], ['Referer' => 'http://hyperion-vault.test']);
        $clearRes->assertStatus(422);
        $clearRes->assertJsonValidationErrors(['type']);

        // 4. Missing telemetry message triggers 422
        $telemetryRes = $this->postJson('/api/v1/telemetry/errors', []);
        $telemetryRes->assertStatus(422);
        $telemetryRes->assertJsonValidationErrors(['message']);
    }
}
