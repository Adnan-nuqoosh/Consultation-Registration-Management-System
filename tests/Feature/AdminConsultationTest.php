<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminConsultationTest extends TestCase
{
    use RefreshDatabase;

    private function consultation(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'reference_number' => 'CONS-2026-00001',
            'full_name' => 'Ahmed Mohamed',
            'company' => 'ABC Trading LLC',
            'job_title' => 'Export Manager',
            'email' => 'ahmed@example.com',
            'phone' => '+971501234567',
            'product_group' => ['Coffee'],
            'export_status' => 'Already exporting',
            'support_needed' => 'Market expansion',
            'preferred_consultation_time' => 'Morning',
            'privacy_consent' => true,
            'status' => 'New',
        ], $overrides));
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->getJson('/api/admin/consultations')->assertUnauthorized();
    }

    public function test_manager_can_change_status_and_history_is_saved(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $consultation = $this->consultation();
        Sanctum::actingAs($manager);

        $this->patchJson('/api/admin/consultations/'.$consultation->id.'/status', [
            'status' => 'Contacted',
            'note' => 'Called applicant',
        ])->assertOk()->assertJsonPath('data.status', 'Contacted');

        $this->assertDatabaseHas('consultation_status_histories', [
            'consultation_request_id' => $consultation->id,
            'changed_by_id' => $manager->id,
            'from_status' => 'New',
            'to_status' => 'Contacted',
            'note' => 'Called applicant',
        ]);
    }

    public function test_agent_only_sees_assigned_requests(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $this->consultation(['reference_number' => 'CONS-2026-00001', 'assigned_to' => $agent->id]);
        $this->consultation(['reference_number' => 'CONS-2026-00002', 'email' => 'other@example.com']);
        Sanctum::actingAs($agent);

        $response = $this->getJson('/api/admin/consultations')->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('CONS-2026-00001', $response->json('data.0.reference'));
    }

    public function test_agent_cannot_read_or_change_unassigned_request(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $consultation = $this->consultation();
        Sanctum::actingAs($agent);

        $this->getJson('/api/admin/consultations/'.$consultation->id)->assertNotFound();
        $this->patchJson('/api/admin/consultations/'.$consultation->id.'/status', ['status' => 'Contacted'])->assertNotFound();
        $this->postJson('/api/admin/consultations/'.$consultation->id.'/notes', ['note' => 'Unauthorized'])->assertNotFound();
    }

    public function test_manager_cannot_delete_requests(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $consultation = $this->consultation();
        Sanctum::actingAs($manager);

        $this->deleteJson('/api/admin/consultations/'.$consultation->id)->assertForbidden();
        $this->assertDatabaseHas('consultation_requests', ['id' => $consultation->id, 'deleted_at' => null]);
    }
}
