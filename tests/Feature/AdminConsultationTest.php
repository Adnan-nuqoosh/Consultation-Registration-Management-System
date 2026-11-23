<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\User;
use App\Models\WebsiteFormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminConsultationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function consultation(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'reference_number' => 'CONS-2026-00001',
            'full_name' => 'Ahmed Mohamed',
            'company' => 'ABC Trading LLC',
            'job_title' => 'Export Manager',
            'email' => 'ahmed@example.com',
            'phone' => '+971501234567',
            'product_group' => ['Coffee', 'Dairy'],
            'export_status' => 'Already exporting',
            'support_needed' => 'Market expansion',
            'preferred_consultation_time' => 'Morning',
            'privacy_consent' => true,
            'status' => 'New',
        ], $overrides));
    }

    private function websiteForm(string $type, array $payload): WebsiteFormSubmission
    {
        return WebsiteFormSubmission::create([
            'reference_number' => 'WEB-'.strtoupper($type).'-20261123-'.strtoupper(bin2hex(random_bytes(4))),
            'form_type' => $type,
            'name' => $payload['name'] ?? null,
            'company' => $payload['company'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'payload' => $payload,
            'status' => 'New',
        ]);
    }

    public function test_dashboard_submission_list_requires_authentication(): void
    {
        $this->getJson('/api/admin/submissions')->assertUnauthorized();
    }

    public function test_admin_can_view_one_combined_list_for_all_five_forms(): void
    {
        $this->consultation();
        foreach (['contact', 'sourcing', 'meeting', 'event'] as $type) {
            $this->websiteForm($type, [
                'name' => 'Visitor '.ucfirst($type),
                'email' => $type.'@example.com',
                'message' => 'A request for '.$type,
            ]);
        }

        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/admin/submissions')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 5);

        $types = collect($response->json('data'))->pluck('form_type')->all();
        $this->assertEqualsCanonicalizing(
            ['consultation', 'contact', 'sourcing', 'meeting', 'event'],
            $types
        );

        $this->getJson('/api/admin/submissions?form_type=event')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.form_type', 'event');
    }

    public function test_admin_can_view_all_consultation_fields(): void
    {
        $consultation = $this->consultation();
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/admin/submissions/consultation/'.$consultation->id)
            ->assertOk()
            ->assertJsonPath('data.form_type', 'consultation')
            ->assertJsonPath('data.reference', 'CONS-2026-00001')
            ->assertJsonPath('data.details.full_name', 'Ahmed Mohamed')
            ->assertJsonPath('data.details.job_title', 'Export Manager')
            ->assertJsonPath('data.details.product_group.0', 'Coffee')
            ->assertJsonPath('data.details.support_needed', 'Market expansion')
            ->assertJsonPath('data.details.privacy_consent', true);
    }

    public function test_admin_can_view_complete_dynamic_website_form_payload(): void
    {
        $submission = $this->websiteForm('meeting', [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'message' => 'Please arrange a meeting.',
            'preferences' => ['morning', 'online'],
            'custom_question' => ['choice' => 'option-a'],
        ]);
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/admin/submissions/meeting/'.$submission->id)
            ->assertOk()
            ->assertJsonPath('data.form_type', 'meeting')
            ->assertJsonPath('data.details.message', 'Please arrange a meeting.')
            ->assertJsonPath('data.details.preferences.1', 'online')
            ->assertJsonPath('data.details.custom_question.choice', 'option-a');
    }

    public function test_dashboard_access_is_limited_to_active_admin_accounts(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'manager', 'is_active' => true]));
        $this->getJson('/api/admin/submissions')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin', 'is_active' => false]));
        $this->getJson('/api/admin/submissions')->assertForbidden();
    }
}
