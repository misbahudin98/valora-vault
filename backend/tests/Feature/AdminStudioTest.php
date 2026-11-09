<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class AdminStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('sanctum.stateful', ['valora-vault.test', 'template.org', 'localhost']);
        Config::set('session.driver', 'file');
    }

    public function test_admin_can_list_existing_feature_specs(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@valora-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/api/v1/admin/studio/modules');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['filename', 'code', 'title', 'module', 'actor', 'status', 'size_bytes', 'updated_at']
            ],
            'doc_path'
        ]);
    }

    public function test_non_admin_cannot_access_studio(): void
    {
        $user = User::factory()->create([
            'email' => 'user@valora-vault.test',
            'role' => ['user'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/api/v1/admin/studio/modules');

        $response->assertStatus(403);
    }

    public function test_admin_can_generate_and_save_spec_atomically(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@valora-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);

        $payload = [
            'code' => '99',
            'slug' => 'test_feature_mock',
            'title' => 'Modul Uji Coba Studio',
            'module' => 'Pengujian Studio',
            'actor' => 'Admin, Tester',
            'status' => 'Draft',
            'user_story' => [
                'role' => 'Auditor',
                'want' => 'menguji pipeline SDD studio otomatis',
                'so_that' => 'keselarasan dokumen dan kode terjaga 100%'
            ],
            'acceptance_criteria' => [
                [
                    'title' => 'Happy Path Uji Coba',
                    'given' => 'Admin mengirimkan payload spesifikasi',
                    'when' => 'Endpoint saveSpec dieksekusi',
                    'then' => 'File markdown tersimpan di doc/features/'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/studio/generate-spec', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'file' => 'doc/features/99_test_feature_mock.md'
        ]);

        $testFile = dirname(base_path()) . '/doc/features/99_test_feature_mock.md';
        $this->assertFileExists($testFile);

        // Cleanup temporary test file
        if (file_exists($testFile)) {
            @unlink($testFile);
        }
    }

    public function test_blueprint_studio_form_request_validations(): void
    {
        $admin = User::factory()->create([
            'email' => 'studio_validator@valora-vault.test',
            'role' => ['admin'],
            'is_active' => true,
        ]);

        // 1. Missing required fields triggers 422
        $res = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/studio/generate-spec', []);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['code', 'slug', 'title', 'module', 'actor']);

        // 2. Invalid slug with spaces or uppercase triggers 422
        $invalidSlugRes = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/studio/generate-spec', [
            'code' => '99',
            'slug' => 'INVALID SLUG WITH SPACES!',
            'title' => 'Title Test',
            'module' => 'Module Test',
            'actor' => 'User',
        ]);
        $invalidSlugRes->assertStatus(422);
        $invalidSlugRes->assertJsonValidationErrors(['slug']);
    }
}
