<?php

use App\\Http\\Controllers\\Api\\Admin\\AdminSubmissionController;
use App\\Http\\Controllers\\Api\\AuthController;
use App\\Http\\Controllers\\Api\\PublicConsultationController;
use App\\Http\\Controllers\\Api\\PublicWebsiteFormController;
use Illuminate\\Support\\Facades\\Route;

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

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'can:access-admin-dashboard'])
    ->group(function (): void {
        Route::get('submissions', [AdminSubmissionController::class, 'index']);
        Route::get('submissions/{formType}/{submission}', [AdminSubmissionController::class, 'show'])
            ->whereIn('formType', ['consultation', 'contact', 'sourcing', 'meeting', 'event'])
            ->whereNumber('submission');
    });
