<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicConsultationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ahmed Mohamed',
            'company' => 'ABC Trading LLC',
            'job_title' => 'Export Manager',
            'email' => 'ahmed@example.com',
            'phone' => '+971501234567',
            'product_group' => 'Coffee, Dairy',
            'export_status' => 'Already exporting',
            'support_needed' => 'Export market expansion and business consultation',
            'preferred_consultation_time' => 'Morning',
            'privacy_consent' => true,
        ], $overrides);
    }

    public function test_public_form_creates_request_and_reference(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/consultations', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'New');
        $this->assertMatchesRegularExpression('/^CONS-\d{4}-\d{5}$/', $response->json('data.reference'));

        $this->assertDatabaseHas('consultation_requests', [
            'full_name' => 'Ahmed Mohamed',
            'email' => 'ahmed@example.com',
            'status' => 'New',
            'privacy_consent' => true,
        ]);
        $this->assertDatabaseHas('consultation_status_histories', [
            'to_status' => 'New',
            'from_status' => null,
        ]);
    }

    public function test_privacy_consent_is_mandatory(): void
    {
        $this->postJson('/api/consultations', $this->payload(['privacy_consent' => false]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('privacy_consent');

        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_email_and_phone_are_validated(): void
    {
        $this->postJson('/api/consultations', $this->payload([
            'email' => 'not-an-email',
            'phone' => 'abc',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_string_product_group_is_normalized_to_array(): void
    {
        Mail::fake();

        $this->postJson('/api/consultations', $this->payload())
            ->assertCreated();

        $consultation = \App\Models\ConsultationRequest::firstOrFail();
        $this->assertSame(['Coffee', 'Dairy'], $consultation->product_group);
    }
}
