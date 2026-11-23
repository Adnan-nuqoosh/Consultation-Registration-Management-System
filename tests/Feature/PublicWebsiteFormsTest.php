<?php

namespace Tests\Feature;

use App\Models\WebsiteFormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicWebsiteFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_four_public_form_types_can_be_submitted_and_keep_their_payload(): void
    {
        Mail::fake();

        foreach (WebsiteFormSubmission::FORM_TYPES as $type) {
            $payload = [
                'name' => 'Ayesha Khan',
                'company' => 'Example Trading',
                'email' => 'ayesha@example.com',
                'phone' => '+971501234567',
                'custom_question' => 'Value specific to '.$type,
                'multi_select' => ['Option A', 'Option B'],
            ];

            $response = $this->postJson('/api/website-forms/'.$type, ['payload' => $payload]);

            $response->assertCreated()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.form_type', $type)
                ->assertJsonPath('data.status', 'New');

            $this->assertDatabaseHas('website_form_submissions', [
                'form_type' => $type,
                'email' => 'ayesha@example.com',
                'status' => 'New',
            ]);
        }

        $this->assertDatabaseCount('website_form_submissions', 4);
        $this->assertDatabaseCount('website_form_status_histories', 4);

        $stored = WebsiteFormSubmission::where('form_type', 'sourcing')->firstOrFail();
        $this->assertSame('Value specific to sourcing', $stored->payload['custom_question']);
        $this->assertSame(['Option A', 'Option B'], $stored->payload['multi_select']);
        $this->assertMatchesRegularExpression('/^WEB-SOURCING-\d{8}-[A-F0-9]{8}$/', $stored->reference_number);
    }

    public function test_raw_flat_form_body_is_accepted_and_normalized(): void
    {
        Mail::fake();

        $this->postJson('/api/website-forms/contact', [
            'full_name' => 'Ahmed Ali',
            'company_name' => 'ABC LLC',
            'email' => 'ahmed@example.com',
            'message' => 'Please contact me.',
        ])->assertCreated();

        $this->assertDatabaseHas('website_form_submissions', [
            'form_type' => 'contact',
            'name' => 'Ahmed Ali',
            'company' => 'ABC LLC',
            'email' => 'ahmed@example.com',
        ]);
    }

    public function test_invalid_email_is_rejected_and_submission_is_not_saved(): void
    {
        Mail::fake();

        $this->postJson('/api/website-forms/event', [
            'payload' => ['name' => 'Test User', 'email' => 'invalid-email'],
        ])->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('website_form_submissions', 0);
    }

    public function test_unknown_form_type_is_not_available(): void
    {
        $this->postJson('/api/website-forms/newsletter', [
            'payload' => ['email' => 'person@example.com'],
        ])->assertNotFound();

        $this->assertDatabaseCount('website_form_submissions', 0);
    }

    public function test_oversized_or_empty_payload_is_rejected(): void
    {
        $this->postJson('/api/website-forms/meeting', ['payload' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payload');

        $this->assertDatabaseCount('website_form_submissions', 0);
    }
}
