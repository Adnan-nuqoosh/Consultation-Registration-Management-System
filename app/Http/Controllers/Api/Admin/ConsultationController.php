<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\ConsultationNote;
use App\Models\ConsultationRequest;
use App\Models\ConsultationStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $paginator = $this->applyFilters(ConsultationRequest::query()->with('assignee:id,name,email'), $filters, $request->user())
            ->latest()
            ->paginate($filters['per_page'] ?? 25)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(fn (ConsultationRequest $item) => $this->serialize($item))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, ConsultationRequest $consultation)
    {
        $this->ensureAssignedAccess($request, $consultation);
        $consultation->load(['assignee:id,name,email', 'notes.author:id,name,email', 'statusHistory.actor:id,name,email']);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($consultation) + [
                'privacy_consent' => $consultation->privacy_consent,
                'notes' => $consultation->notes->map(fn (ConsultationNote $note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'author' => $note->author?->only(['id', 'name', 'email']),
                    'created_at' => $note->created_at?->toISOString(),
                ]),
                'status_history' => $consultation->statusHistory->map(fn (ConsultationStatusHistory $history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'note' => $history->note,
                    'changed_by' => $history->actor?->only(['id', 'name', 'email']),
                    'changed_at' => $history->changed_at?->toISOString(),
                ]),
            ],
        ]);
    }

    public function update(UpdateConsultationRequest $request, ConsultationRequest $consultation)
    {
        $consultation->update($request->validated());
        $consultation->refresh()->load('assignee:id,name,email');

        return response()->json(['success' => true, 'message' => 'Consultation request updated.', 'data' => $this->serialize($consultation)]);
    }

    public function updateStatus(Request $request, ConsultationRequest $consultation)
    {
        $this->ensureAssignedAccess($request, $consultation);
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(ConsultationRequest::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $consultation, $validated): void {
            $locked = ConsultationRequest::query()->lockForUpdate()->findOrFail($consultation->id);
            $oldStatus = $locked->status;
            if ($oldStatus === $validated['status']) {
                return;
            }

            $locked->update(['status' => $validated['status']]);
            ConsultationStatusHistory::create([
                'consultation_request_id' => $locked->id,
                'changed_by_id' => $request->user()->id,
                'from_status' => $oldStatus,
                'to_status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'changed_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Consultation status updated.',
            'data' => ['id' => $consultation->id, 'status' => $validated['status']],
        ]);
    }

    public function addNote(Request $request, ConsultationRequest $consultation)
    {
        $this->ensureAssignedAccess($request, $consultation);
        $validated = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        $note = ConsultationNote::create([
            'consultation_request_id' => $consultation->id,
            'author_id' => $request->user()->id,
            'note' => $validated['note'],
        ]);

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

    public function assign(Request $request, ConsultationRequest $consultation)
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)->whereIn('role', ['manager', 'agent']))],
        ]);

        $consultation->update(['assigned_to' => $validated['assigned_to']]);
        $consultation->refresh()->load('assignee:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Consultation request assigned.',
            'data' => ['id' => $consultation->id, 'assigned_to' => $consultation->assignee?->only(['id', 'name', 'email'])],
        ]);
    }

    public function destroy(ConsultationRequest $consultation)
    {
        Gate::authorize('consultations.delete');
        $consultation->delete();

        return response()->json(['success' => true, 'message' => 'Consultation request deleted.']);
    }

    public function stats(Request $request)
    {
        $base = $this->applyFilters(ConsultationRequest::query(), [], $request->user());
        $total = (clone $base)->count();
        $counts = (clone $base)->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')->pluck('aggregate', 'status');

        $statuses = collect(ConsultationRequest::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)]);

        return response()->json([
            'success' => true,
            'data' => ['total' => $total, 'by_status' => $statuses],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = $this->applyFilters(ConsultationRequest::query()->with('assignee:id,name'), $filters, $request->user())
            ->orderByDesc('created_at');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Reference', 'Full Name', 'Company', 'Job Title', 'Email', 'Phone',
                'Product Group', 'Export Status', 'Support Needed', 'Preferred Consultation Time',
                'Privacy Consent', 'Status', 'Assigned To', 'Submitted At',
            ]);

            $query->chunk(500, function ($rows) use ($output): void {
                foreach ($rows as $row) {
                    $values = [
                        $row->reference_number, $row->full_name, $row->company, $row->job_title,
                        $row->email, $row->phone, implode(', ', $row->product_group ?? []),
                        $row->export_status, $row->support_needed, $row->preferred_consultation_time,
                        $row->privacy_consent ? 'Yes' : 'No', $row->status, $row->assignee?->name,
                        $row->created_at?->toISOString(),
                    ];
                    fputcsv($output, array_map([$this, 'neutralizeCsvCell'], $values));
                }
            });

            fclose($output);
        }, 'consultation-requests-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', Rule::in(ConsultationRequest::STATUSES)],
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

        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function (Builder $nested) use ($term): void {
                $nested->where('reference_number', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
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

    private function ensureAssignedAccess(Request $request, ConsultationRequest $consultation): void
    {
        if ($request->user()->role === 'agent' && $consultation->assigned_to !== $request->user()->id) {
            abort(404);
        }
    }

    private function serialize(ConsultationRequest $consultation): array
    {
        return [
            'id' => $consultation->id,
            'reference' => $consultation->reference_number,
            'full_name' => $consultation->full_name,
            'company' => $consultation->company,
            'job_title' => $consultation->job_title,
            'email' => $consultation->email,
            'phone' => $consultation->phone,
            'product_group' => $consultation->product_group,
            'export_status' => $consultation->export_status,
            'support_needed' => $consultation->support_needed,
            'preferred_consultation_time' => $consultation->preferred_consultation_time,
            'status' => $consultation->status,
            'assigned_to' => $consultation->assignee?->only(['id', 'name', 'email']),
            'submitted_at' => $consultation->created_at?->toISOString(),
            'updated_at' => $consultation->updated_at?->toISOString(),
        ];
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
