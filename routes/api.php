<?php

use App\Http\Controllers\Api\Admin\ConsultationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicConsultationController;
use App\Http\Controllers\Api\PublicWebsiteFormController;
use App\Http\Controllers\Api\Admin\WebsiteFormSubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::post('consultations', [PublicConsultationController::class, 'store'])
    ->middleware('throttle:consultations');

Route::post('website-forms/{formType}', [PublicWebsiteFormController::class, 'store'])
    ->whereIn('formType', ['contact', 'sourcing', 'meeting', 'event'])
    ->middleware('throttle:website-forms');

Route::prefix('admin')->middleware('auth:sanctum')->group(function (): void {
    Route::get('website-forms-stats', [WebsiteFormSubmissionController::class, 'stats'])->middleware('can:website_forms.stats');
    Route::get('website-forms-export', [WebsiteFormSubmissionController::class, 'export'])->middleware('can:website_forms.export');
    Route::get('website-forms', [WebsiteFormSubmissionController::class, 'index'])->middleware('can:website_forms.view');
    Route::get('website-forms/{submission}', [WebsiteFormSubmissionController::class, 'show'])->middleware('can:website_forms.view');
    Route::patch('website-forms/{submission}', [WebsiteFormSubmissionController::class, 'update'])->middleware('can:website_forms.update');
    Route::patch('website-forms/{submission}/status', [WebsiteFormSubmissionController::class, 'updateStatus'])->middleware('can:website_forms.update_status');
    Route::post('website-forms/{submission}/notes', [WebsiteFormSubmissionController::class, 'addNote'])->middleware('can:website_forms.add_notes');
    Route::post('website-forms/{submission}/assign', [WebsiteFormSubmissionController::class, 'assign'])->middleware('can:website_forms.assign');
    Route::delete('website-forms/{submission}', [WebsiteFormSubmissionController::class, 'destroy'])->middleware('can:website_forms.delete');

    Route::get('consultation-stats', [ConsultationController::class, 'stats'])->middleware('can:consultations.stats');
    Route::get('team-members', [ConsultationController::class, 'teamMembers'])->middleware('can:consultations.assign');
    Route::get('consultations-export', [ConsultationController::class, 'export'])->middleware('can:consultations.export');
    Route::get('consultations', [ConsultationController::class, 'index'])->middleware('can:consultations.view');
    Route::get('consultations/{consultation}', [ConsultationController::class, 'show'])->middleware('can:consultations.view');
    Route::patch('consultations/{consultation}', [ConsultationController::class, 'update'])->middleware('can:consultations.update');
    Route::patch('consultations/{consultation}/status', [ConsultationController::class, 'updateStatus'])->middleware('can:consultations.update_status');
    Route::post('consultations/{consultation}/notes', [ConsultationController::class, 'addNote'])->middleware('can:consultations.add_notes');
    Route::post('consultations/{consultation}/assign', [ConsultationController::class, 'assign'])->middleware('can:consultations.assign');
    Route::delete('consultations/{consultation}', [ConsultationController::class, 'destroy'])->middleware('can:consultations.delete');
});
