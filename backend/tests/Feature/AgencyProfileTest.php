<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AgencyProfile;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ClientProjectPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_view_agency_profile(): void
    {
        $admin = User::factory()->create(['role' => ['admin']]);

        $response = $this->actingAs($admin)->getJson('/api/v1/agency-profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'agency' => [
                    'id',
                    'agency_name',
                    'legal_name',
                    'email',
                    'hotline_wa',
                    'operational_address',
                    'github_url',
                    'website_url',
                    'logo_url',
                    'tax_id',
                    'bank_name',
                    'bank_account_no',
                    'bank_account_name',
                    'signatory_name',
                    'signatory_title',
                    'terms_notes',
                ],
            ]);
    }

    public function test_admin_can_update_agency_profile(): void
    {
        $admin = User::factory()->create(['role' => ['admin']]);

        $payload = [
            'agency_name' => 'Valora Studio Nusantara',
            'legal_name' => 'PT Valora Studio Nusantara',
            'tagline' => 'High-Performance Software Agency',
            'email' => 'halo@valorastudio.id',
            'hotline_wa' => '+62 811-2233-4455',
            'operational_address' => 'One Pacific Place Suite 12, SCBD, Jakarta Selatan',
            'github_url' => 'https://github.com/valora-vault/enterprise',
            'website_url' => 'https://valorastudio.id',
            'logo_url' => 'https://valorastudio.id/assets/logo.svg',
            'tax_id' => '99.888.777.6-555.000',
            'bank_name' => 'Bank Mandiri',
            'bank_account_no' => '123-000-999-888',
            'bank_account_name' => 'PT Valora Studio Nusantara',
            'secondary_bank_name' => 'Bank BCA',
            'secondary_bank_account_no' => '543-210-987-6',
            'secondary_bank_account_name' => 'PT Valora Studio Nusantara',
            'signatory_name' => 'Arkananta Daniswara',
            'signatory_title' => 'Chief Executive Officer',
            'terms_notes' => 'Pembayaran termin 1 wajib dilunasi sebelum deployment staging.',
        ];

        $response = $this->actingAs($admin)->putJson('/api/v1/admin/agency-profile', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('agency.agency_name', 'Valora Studio Nusantara')
            ->assertJsonPath('agency.legal_name', 'PT Valora Studio Nusantara')
            ->assertJsonPath('agency.email', 'halo@valorastudio.id')
            ->assertJsonPath('agency.bank_name', 'Bank Mandiri')
            ->assertJsonPath('agency.bank_account_no', '123-000-999-888')
            ->assertJsonPath('agency.signatory_name', 'Arkananta Daniswara');

        $this->assertDatabaseHas('agency_profiles', [
            'agency_name' => 'Valora Studio Nusantara',
            'email' => 'halo@valorastudio.id',
            'bank_account_no' => '123-000-999-888',
        ]);
    }

    public function test_non_admin_cannot_update_agency_profile(): void
    {
        config([
            'permissions.roles' => [
                'admin' => ['*'],
                'viewer' => ['profile.view'],
            ],
        ]);

        $viewer = User::factory()->create(['role' => ['viewer']]);

        $response = $this->actingAs($viewer)->putJson('/api/v1/admin/agency-profile', [
            'agency_name' => 'Hacked Agency',
            'email' => 'hacked@agency.com',
            'hotline_wa' => '+62 899-0000-1111',
            'operational_address' => 'Unknown',
            'bank_name' => 'BCA',
            'bank_account_no' => '000-111',
            'bank_account_name' => 'Hacker',
            'signatory_name' => 'Hacker Guy',
            'signatory_title' => 'Director',
        ]);

        $response->assertStatus(403);
    }

    public function test_validation_requires_mandatory_fields_when_updating(): void
    {
        $admin = User::factory()->create(['role' => ['admin']]);

        $response = $this->actingAs($admin)->putJson('/api/v1/admin/agency-profile', [
            'agency_name' => '',
            'email' => 'not-an-email',
            'hotline_wa' => '',
            'operational_address' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'agency_name',
                'email',
                'hotline_wa',
                'operational_address',
                'bank_name',
                'bank_account_no',
                'bank_account_name',
                'signatory_name',
                'signatory_title',
            ]);
    }

    public function test_invoice_and_receipt_endpoints_embed_dynamic_agency_profile(): void
    {
        $admin = User::factory()->create(['role' => ['admin']]);

        // Update agency profile
        AgencyProfile::getProfile()->update([
            'agency_name' => 'Valora Dynamic Agency',
            'legal_name' => 'PT Valora Dynamic Agency',
            'email' => 'billing@valoradynamic.com',
            'hotline_wa' => '+62 888-7777-6666',
            'operational_address' => 'Cyber 2 Tower Lt. 18, Jakarta',
            'tax_id' => '12.345.678.9-001.000',
            'bank_name' => 'Bank Syariah Indonesia',
            'bank_account_no' => '711-222-333-4',
            'bank_account_name' => 'PT Valora Dynamic Agency',
            'signatory_name' => 'M. Rinaldi Pratama',
            'signatory_title' => 'Finance Director',
        ]);

        $client = Client::create([
            'name' => 'PT Pelita Jaya',
            'contact_person' => 'Budi Santoso',
            'email' => 'budi@pelitajaya.com',
            'phone' => '+62 811-1234-5678',
            'address' => 'Kawasan Industri Cikarang',
        ]);

        $project = ClientProject::create([
            'client_id' => $client->id,
            'project_name' => 'Portal ERP Korporat',
            'app_name' => 'Pelita ERP',
            'project_type' => 'enterprise_system',
            'status' => 'in_progress',
            'grand_total' => 50000000,
            'paid_amount' => 20000000,
            'lead_pic' => 'Iqbal Dev',
        ]);

        $payment = ClientProjectPayment::create([
            'project_id' => $project->id,
            'amount' => 20000000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'reference_no' => 'TRX-20261109-01',
            'recorded_by' => 'Admin Finance',
        ]);

        // Check invoice endpoint
        $invoiceRes = $this->actingAs($admin)->getJson("/api/v1/admin/billing/projects/{$project->id}/invoice");
        $invoiceRes->assertStatus(200)
            ->assertJsonPath('agency.agency_name', 'Valora Dynamic Agency')
            ->assertJsonPath('agency.email', 'billing@valoradynamic.com')
            ->assertJsonPath('agency.bank_name', 'Bank Syariah Indonesia')
            ->assertJsonPath('agency.bank_account_no', '711-222-333-4')
            ->assertJsonPath('agency.signatory_name', 'M. Rinaldi Pratama');

        // Check receipt endpoint
        $receiptRes = $this->actingAs($admin)->getJson("/api/v1/admin/billing/payments/{$payment->id}/receipt");
        $receiptRes->assertStatus(200)
            ->assertJsonPath('agency.agency_name', 'Valora Dynamic Agency')
            ->assertJsonPath('agency.bank_name', 'Bank Syariah Indonesia')
            ->assertJsonPath('agency.signatory_name', 'M. Rinaldi Pratama')
            ->assertJsonPath('company.name', 'PT Valora Dynamic Agency')
            ->assertJsonPath('company.email', 'billing@valoradynamic.com');
    }
}
