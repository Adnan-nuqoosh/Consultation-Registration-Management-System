<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebsiteFormSubmissionRequest;
use App\Models\WebsiteFormStatusHistory;
use App\Models\WebsiteFormSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PublicWebsiteFormController extends Controller
{
    public function store(StoreWebsiteFormSubmissionRequest $request, string $formType)
    {
        abort_unless(in_array($formType, WebsiteFormSubmission::FORM_TYPES, true), 404);

        $payload = $request->validated()['payload'];
        $name = $this->firstValue($payload, ['full_name', 'name', 'contact_name', 'your_name']);
        $company = $this->firstValue($payload, ['company', 'company_name', 'organization', 'business_name']);
        $email = $this->firstValue($payload, ['email', 'email_address', 'contact_email']);
        $phone = $this->firstValue($payload, ['phone', 'phone_number', 'mobile', 'mobile_number', 'telephone']);

        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'The submitted email address is invalid.',
                'errors' => ['email' => ['Please provide a valid email address.']],
            ], 422);
        }

        if ($phone !== null && (! preg_match('/^\\+?[0-9][0-9\\s().-]{5,24}$/', $phone)
            || strlen(preg_replace('/\\D+/', '', $phone)) < 7
            || strlen(preg_replace('/\\D+/', '', $phone)) > 15)) {
            return response()->json([
                'success' => false,
                'message' => 'The submitted phone number is invalid.',
                'errors' => ['phone' => ['Please provide a valid phone number.']],
            ], 422);
        }

        $submission = DB::transaction(function () use ($formType, $payload, $name, $company, $email, $phone): WebsiteFormSubmission {
            $submission = WebsiteFormSubmission::create([
                'reference_number' => 'WEB-'.strtoupper($formType).'-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(4))),
                'form_type' => $formType,
                'name' => $name,
                'company' => $company,
                'email' => $email,
                'phone' => $phone,
                'payload' => $payload,
                'status' => 'New',
            ]);

            WebsiteFormStatusHistory::create([
                'website_form_submission_id' => $submission->id,
                'changed_by_id' => null,
                'from_status' => null,
                'to_status' => 'New',
                'note' => 'Submitted through the public website form.',
                'changed_at' => now(),
            ]);

            return $submission;
        });

        $this->sendNotifications($submission);

        return response()->json([
            'success' => true,
            'message' => ucfirst($formType).' form submitted successfully.',
            'data' => [
                'id' => $submission->id,
                'reference' => $submission->reference_number,
                'form_type' => $submission->form_type,
                'status' => $submission->status,
            ],
        ], 201);
    }

    private function firstValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                $value = trim((string) $payload[$key]);
                if ($value !== '') {
                    return mb_substr($value, 0, 190);
                }
            }
        }

        return null;
    }

    private function sendNotifications(WebsiteFormSubmission $submission): void
    {
        $adminEmail = config('website_forms.admin_email');
        $subject = ucfirst($submission->form_type).' form submission received';

        if ($adminEmail) {
            try {
                Mail::raw(
                    "A new {$submission->form_type} form was submitted.\nReference: {$submission->reference_number}\nName: ".($submission->name ?? 'Not provided')."\nCompany: ".($submission->company ?? 'Not provided')."\nEmail: ".($submission->email ?? 'Not provided')."\nPhone: ".($submission->phone ?? 'Not provided')."\n\nPlease sign in to the admin dashboard to review the submission.",
                    fn ($message) => $message->to($adminEmail)->subject($subject)
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($submission->email) {
            try {
                Mail::raw(
                    "Thank you. We have received your {$submission->form_type} form submission. Our team will review it and contact you if further information is needed.\nReference: {$submission->reference_number}",
                    fn ($message) => $message->to($submission->email)->subject('We received your '.$submission->form_type.' form')
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
