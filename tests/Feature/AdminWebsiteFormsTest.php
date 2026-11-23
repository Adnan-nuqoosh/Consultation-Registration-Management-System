<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteFormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWebsiteFormsTest extends TestCase
{
    use RefreshDatabase;

    private function submission(array $overrides = []): WebsiteFormSubmission
    {
        return WebsiteFormSubmission::create(array_merge([
            'reference_number' => 'WEB-CONTACT-20261123-ABCDEF12',
            'form_type' => 'contact',
            'name' => 'Ayesha Khan',
            'company' => 'Example Trading',
            'email' => 'ayesha@example.com',
            'phone' => '+971501234567',
            'payload' => ['name' => 'Ayesha Khan', 'email' => 'ayesha@example.com', 'message' => 'Call me'],
            'status' => 'New',
        ], $overrides));
    }

    public function test_admin_list_requires_authentication(): void
    {
        $this->getJson('/api/admin/website-forms')->assertUnauthorized();
    }

    public function test_manager_can_filter_and_view_form_submission_payload(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $contact = $this->submission();
        $this->submission([
            'reference_number' => 'WEB-EVENT-20261123-ABCDEF13',
            'form_type' => 'event',
            'email' => 'event@example.com',
        ]);
        Sanctum::actingAs($manager);

        $this->getJson('/api/admin/website-forms?form_type=contact&search=Ayesha')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reference', $contact->reference_number);

        $this->getJson('/api/admin/website-forms/'.$contact->id)
            ->assertOk()
            ->assertJsonPath('data.payload.message', 'Call me');
    }

    public function test_manager_can_change_status_and_history_is_saved(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $submission = $this->submission();
        Sanctum::actingAs($manager);

        $this->patchJson('/api/admin/website-forms/'.$submission->id.'/status', [
            'status' => 'Contacted',
            'note' => 'Called the applicant.',
        ])->assertOk()->assertJsonPath('data.status', 'Contacted');

        $this->assertDatabaseHas('website_form_status_histories', [
            'website_form_submission_id' => $submission->id,
            'changed_by_id' => $manager->id,
            'from_status' => 'New',
            'to_status' => 'Contacted',
        ]);
    }

    public function test_manager_can_add_private_note_and_assign_submission(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $agent = User::factory()->create(['role' => 'agent']);
        $submission = $this->submission();
        Sanctum::actingAs($manager);

        $this->postJson('/api/admin/website-forms/'.$submission->id.'/notes', [
            'note' => 'Internal follow-up only.',
        ])->assertCreated();

        $this->postJson('/api/admin/website-forms/'.$submission->id.'/assign', [
            'assigned_to' => $agent->id,
        ])->assertOk();

        $this->assertDatabaseHas('website_form_notes', [
            'website_form_submission_id' => $submission->id,
            'author_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('website_form_submissions', [
            'id' => $submission->id,
            'assigned_to' => $agent->id,
        ]);
        $this->assertDatabaseHas('website_form_activity_logs', [
            'website_form_submission_id' => $submission->id,
            'action' => 'assigned',
        ]);
    }

    public function test_agent_cannot_access_unassigned_form_submission(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $submission = $this->submission();
        Sanctum::actingAs($agent);

        $this->getJson('/api/admin/website-forms/'.$submission->id)->assertNotFound();
        $this->patchJson('/api/admin/website-forms/'.$submission->id.'/status', [
            'status' => 'Contacted',
        ])->assertNotFound();
        $this->postJson('/api/admin/website-forms/'.$submission->id.'/notes', [
            'note' => 'No access',
        ])->assertNotFound();
    }

    public function test_manager_cannot_delete_but_admin_can_soft_delete(): void
    {
        $submission = $this->submission();
        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $this->deleteJson('/api/admin/website-forms/'.$submission->id)->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->deleteJson('/api/admin/website-forms/'.$submission->id)->assertOk();
        $this->assertSoftDeleted('website_form_submissions', ['id' => $submission->id]);
    }

    public function test_stats_and_csv_export_are_available_to_manager(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $this->submission();
        Sanctum::actingAs($manager);

        $this->getJson('/api/admin/website-forms-stats')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.by_form_type.contact', 1);

        $this->get('/api/admin/website-forms-export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
