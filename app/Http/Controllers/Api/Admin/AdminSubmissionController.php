<?php

namespace App\\Http\\Controllers\\Api\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\ConsultationRequest;
use App\\Models\\WebsiteFormSubmission;
use Illuminate\\Database\\Query\\Builder;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;

class AdminSubmissionController extends Controller
{
    private const FORM_TYPES = ['consultation', 'contact', 'sourcing', 'meeting', 'event'];

    /**
     * Return a single, date-ordered list across all five public website forms.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'form_type' => ['nullable', 'string', Rule::in(self::FORM_TYPES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $consultations = DB::table('consultation_requests')
            ->whereNull('deleted_at')
            ->selectRaw("'consultation' as form_type, id, reference_number as reference, full_name as name, company, email, phone, created_at as submitted_at");

        $websiteForms = DB::table('website_form_submissions')
            ->whereNull('deleted_at')
            ->selectRaw('form_type, id, reference_number as reference, name, company, email, phone, created_at as submitted_at');

        $query = DB::query()->fromSub(
            $consultations->unionAll($websiteForms),
            'submissions'
        );

        if (! empty($filters['form_type'])) {
            $query->where('form_type', $filters['form_type']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('submitted_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('submitted_at', '<=', $filters['to']);
        }

        $paginator = $query
            ->orderByDesc('submitted_at')
            ->orderBy('form_type')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (object $submission): array => [
                'id' => (int) $submission->id,
                'reference' => $submission->reference,
                'form_type' => $submission->form_type,
                'name' => $submission->name,
                'company' => $submission->company,
                'email' => $submission->email,
                'phone' => $submission->phone,
                'submitted_at' => $submission->submitted_at,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return every field submitted by the visitor for the selected form.
     */
    public function show(string $formType, int $submission)
    {
        abort_unless(in_array($formType, self::FORM_TYPES, true), 404);

        if ($formType === 'consultation') {
            $record = ConsultationRequest::query()->findOrFail($submission);

            $details = [
                'full_name' => $record->full_name,
                'company' => $record->company,
                'job_title' => $record->job_title,
                'email' => $record->email,
                'phone' => $record->phone,
                'product_group' => $record->product_group,
                'export_status' => $record->export_status,
                'support_needed' => $record->support_needed,
                'preferred_consultation_time' => $record->preferred_consultation_time,
                'privacy_consent' => $record->privacy_consent,
            ];
        } else {
            $record = WebsiteFormSubmission::query()
                ->where('form_type', $formType)
                ->findOrFail($submission);

            // Keep the original dynamic fields and nested values intact.
            $details = $record->payload ?? [];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'reference' => $record->reference_number,
                'form_type' => $formType,
                'submitted_at' => $record->created_at?->toISOString(),
                'details' => $details,
            ],
        ]);
    }
}
