<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWebsiteFormSubmissionRequest;
use App\Models\User;
use App\Models\WebsiteFormActivityLog;
use App\Models\WebsiteFormNote;
use App\Models\WebsiteFormStatusHistory;
use App\Models\WebsiteFormSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WebsiteFormSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $paginator = $this->applyFilters(
            WebsiteFormSubmission::query()->with('assignee:id,name,email'),
            $filters,
            $request->user()
        )->latest()->paginate($filters['per_page'] ?? 25)->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(fn (WebsiteFormSubmission $item) => $this->serialize($item))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, WebsiteFormSubmission $submission)
    {
        $this->ensureAssignedAccess($request, $submission);
        $submission->load([
            'assignee:id,name,email',
            'notes.author:id,name,email',
            'statusHistory.actor:id,name,email',
            'activities.actor:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($submission) + [
                'payload' => $submission->payload,
                'notes' => $submission->notes->map(fn (WebsiteFormNote $note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'author' => $note->author?->only(['id', 'name', 'email']),
                    'created_at' => $note->created_at?->toISOString(),
                ]),
                'status_history' => $submission->statusHistory->map(fn (WebsiteFormStatusHistory $history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'note' => $history->note,
                    'changed_by' => $history->actor?->only(['id', 'name', 'email']),
                    'changed_at' => $history->changed_at?->toISOString(),
                ]),
                'activity_log' => $submission->activities->map(fn (WebsiteFormActivityLog $activity) => [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'details' => $activity->details,
                    'actor' => $activity->actor?->only(['id', 'name', 'email']),
                    'created_at' => $activity->created_at?->toISOString(),
                ]),
            ],
        ]);
    }

    public function update(UpdateWebsiteFormSubmissionRequest $request, WebsiteFormSubmission $submission)
    {
        $oldPayload = $submission->payload;
        $newPayload = $request->validated()['payload'] ?? $oldPayload;
        $submission->update([
            'payload' => $newPayload,
            'name' => $this->firstValue($newPayload, ['full_name', 'name', 'contact_name', 'your_name']),
            'company' => $this->firstValue($newPayload, ['company', 'company_name', 'organization', 'business_name']),
            'email' => $this->firstValue($newPayload, ['email', 'email_address', 'contact_email']),
            'phone' => $this->firstValue($newPayload, ['phone', 'phone_number', 'mobile', 'mobile_number', 'telephone']),
        ]);

        if ($oldPayload !== $newPayload) {
            $this->log($submission, $request->user(), 'payload_updated', [
                'changed_keys' => array_values(array_unique(array_merge(array_keys($oldPayload ?? []), array_keys($newPayload ?? [])))),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Form submission updated.',
            'data' => $this->serialize($submission->refresh()->load('assignee:id,name,email')),
        ]);
    }

    public function updateStatus(Request $request, WebsiteFormSubmission $submission)
    {
        $this->ensureAssignedAccess($request, $submission);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(WebsiteFormSubmission::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $submission, $validated): void {
            $locked = WebsiteFormSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $oldStatus = $locked->status;

            if ($oldStatus === $validated['status']) {
                return;
            }

            $locked->update(['status' => $validated['status']]);
            WebsiteFormStatusHistory::create([
                'website_form_submission_id' => $locked->id,
                'changed_by_id' => $request->user()->id,
                'from_status' => $oldStatus,
                'to_status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'changed_at' => now(),
            ]);
            WebsiteFormActivityLog::create([
                'website_form_submission_id' => $locked->id,
                'actor_id' => $request->user()->id,
                'action' => 'status_changed',
                'details' => ['from' => $oldStatus, 'to' => $validated['status']],
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Form submission status updated.',
            'data' => ['id' => $submission->id, 'status' => $validated['status']],
        ]);
    }

    public function addNote(Request $request, WebsiteFormSubmission $submission)
    {
        $this->ensureAssignedAccess($request, $submission);
        $validated = $request->validate(['note' => ['required', 'string', 'max:5000']]);

        $note = WebsiteFormNote::create([
            'website_form_submission_id' => $submission->id,
            'author_id' => $request->user()->id,
            'note' => $validated['note'],
        ]);

        $this->log($submission, $request->user(), 'internal_note_added', ['note_id' => $note->id]);

        return response()->json([
            'success' => true,
            'message' => 'Internal note added.',
            'data' => [
                'id' => $note->id,
                'note' => $note->note,
                'author' => $request->user()->only(['id', 'name', 'email']),
                'created_at' => $note->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function assign(Request $request, WebsiteFormSubmission $submission)
    {
        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn('role', ['manager', 'agent'])),
            ],
        ]);

        $previousAssignee = $submission->assigned_to;
        $submission->update(['assigned_to' => $validated['assigned_to']]);

        if ((int) $previousAssignee !== (int) $validated['assigned_to']) {
            $this->log($submission, $request->user(), 'assigned', [
                'from_user_id' => $previousAssignee,
                'to_user_id' => $validated['assigned_to'],
            ]);
        }

        $submission->refresh()->load('assignee:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Form submission assigned.',
            'data' => [
                'id' => $submission->id,
                'assigned_to' => $submission->assignee?->only(['id', 'name', 'email']),
            ],
        ]);
    }

    public function stats(Request $request)
    {
        $base = $this->applyFilters(WebsiteFormSubmission::query(), [], $request->user());

        $total = (clone $base)->count();
        $byType = (clone $base)->select('form_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('form_type')->pluck('aggregate', 'form_type');

        $byStatus = (clone $base)->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')->pluck('aggregate', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_form_type' => collect(WebsiteFormSubmission::FORM_TYPES)
                    ->mapWithKeys(fn (string $type) => [$type => (int) ($byType[$type] ?? 0)]),
                'by_status' => collect(WebsiteFormSubmission::STATUSES)
                    ->mapWithKeys(fn (string $status) => [$status => (int) ($byStatus[$status] ?? 0)]),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = $this->applyFilters(
            WebsiteFormSubmission::query()->with('assignee:id,name'),
            $filters,
            $request->user()
        )->orderByDesc('created_at');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Reference', 'Form Type', 'Name', 'Company', 'Email', 'Phone',
                'Status', 'Assigned To', 'Payload JSON', 'Submitted At',
            ]);

            $query->chunk(500, function ($rows) use ($output): void {
                foreach ($rows as $row) {
                    $values = [
                        $row->reference_number,
                        $row->form_type,
                        $row->name,
                        $row->company,
                        $row->email,
                        $row->phone,
                        $row->status,
                        $row->assignee?->name,
                        json_encode($row->payload, JSON_UNESCAPED_UNICODE),
                        $row->created_at?->toISOString(),
                    ];
                    fputcsv($output, array_map([$this, 'neutralizeCsvCell'], $values));
                }
            });

            fclose($output);
        }, 'website-form-submissions-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function destroy(Request $request, WebsiteFormSubmission $submission)
    {
        Gate::authorize('website_forms.delete');
        $this->log($submission, $request->user(), 'soft_deleted', [
            'reference' => $submission->reference_number,
        ]);
        $submission->delete();

        return response()->json(['success' => true, 'message' => 'Form submission deleted.']);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'form_type' => ['nullable', 'string', Rule::in(WebsiteFormSubmission::FORM_TYPES)],
            'status' => ['nullable', 'string', Rule::in(WebsiteFormSubmission::STATUSES)],
            'search' => ['nullable', 'string', 'max:150'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        if ($user->role === 'agent') {
            $query->where('assigned_to', $user->id);
        }

        if (! empty($filters['form_type'])) {
            $query->where('form_type', $filters['form_type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function (Builder $nested) use ($term): void {
                $nested->where('reference_number', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereRaw('CAST(payload AS CHAR) like ?', ["%{$term}%"]);
            });
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['assigned_to']) && $user->role !== 'agent') {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query;
    }

    private function ensureAssignedAccess(Request $request, WebsiteFormSubmission $submission): void
    {
        if ($request->user()->role === 'agent' && (int) $submission->assigned_to !== (int) $request->user()->id) {
            abort(404);
        }
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

    private function serialize(WebsiteFormSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'reference' => $submission->reference_number,
            'form_type' => $submission->form_type,
            'name' => $submission->name,
            'company' => $submission->company,
            'email' => $submission->email,
            'phone' => $submission->phone,
            'status' => $submission->status,
            'assigned_to' => $submission->assignee?->only(['id', 'name', 'email']),
            'submitted_at' => $submission->created_at?->toISOString(),
            'updated_at' => $submission->updated_at?->toISOString(),
        ];
    }

    private function log(WebsiteFormSubmission $submission, User $actor, string $action, array $details = []): void
    {
        WebsiteFormActivityLog::create([
            'website_form_submission_id' => $submission->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    private function neutralizeCsvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');
        if ($value !== '' && preg_match('/^[\s]*[=+@\-]/u', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
