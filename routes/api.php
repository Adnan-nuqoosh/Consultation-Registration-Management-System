<?php

use App\Http\Controllers\Api\Admin\ConsultationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicConsultationController;
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

Route::prefix('admin')->middleware('auth:sanctum')->group(function (): void {
    Route::get('consultation-stats', [ConsultationController::class, 'stats'])->middleware('can:consultations.stats');
    Route::get('consultations-export', [ConsultationController::class, 'export'])->middleware('can:consultations.export');
    Route::get('consultations', [ConsultationController::class, 'index'])->middleware('can:consultations.view');
    Route::get('consultations/{consultation}', [ConsultationController::class, 'show'])->middleware('can:consultations.view');
    Route::patch('consultations/{consultation}', [ConsultationController::class, 'update'])->middleware('can:consultations.update');
    Route::patch('consultations/{consultation}/status', [ConsultationController::class, 'updateStatus'])->middleware('can:consultations.update_status');
    Route::post('consultations/{consultation}/notes', [ConsultationController::class, 'addNote'])->middleware('can:consultations.add_notes');
    Route::post('consultations/{consultation}/assign', [ConsultationController::class, 'assign'])->middleware('can:consultations.assign');
    Route::delete('consultations/{consultation}', [ConsultationController::class, 'destroy'])->middleware('can:consultations.delete');
});
