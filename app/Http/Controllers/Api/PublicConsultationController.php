<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsultationRequest;
use App\Models\ConsultationRequest as Consultation;
use App\Models\ConsultationStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PublicConsultationController extends Controller
{
    public function store(StoreConsultationRequest $request)
    {
        $consultation = DB::transaction(function () use ($request): Consultation {
            $consultation = Consultation::create([...$request->validated(), 'status' => 'New']);
            $consultation->reference_number = sprintf('CONS-%s-%05d', now()->format('Y'), $consultation->id);
            $consultation->save();

            ConsultationStatusHistory::create([
                'consultation_request_id' => $consultation->id,
                'changed_by_id' => null,
                'from_status' => null,
                'to_status' => 'New',
                'note' => 'Request submitted through the public website form.',
                'changed_at' => now(),
            ]);

            return $consultation;
        });

        $this->sendNotifications($consultation);

        return response()->json([
            'success' => true,
            'message' => 'Consultation request submitted successfully',
            'data' => ['id' => $consultation->id, 'reference' => $consultation->reference_number, 'status' => $consultation->status],
        ], 201);
    }

    private function sendNotifications(Consultation $consultation): void
    {
        $adminEmail = config('consultations.admin_email');
        if ($adminEmail) {
            try {
                Mail::raw(
                    "A new consultation request has been received.\n\nName: {$consultation->full_name}\nCompany: {$consultation->company}\nEmail: {$consultation->email}\nReference: {$consultation->reference_number}",
                    fn ($message) => $message->to($adminEmail)->subject('New consultation request')
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            Mail::raw(
                "Thank you for registering! Your consultation request has been successfully submitted. Our team will contact you shortly.\n\nReference: {$consultation->reference_number}",
                fn ($message) => $message->to($consultation->email)->subject('Consultation request received')
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
