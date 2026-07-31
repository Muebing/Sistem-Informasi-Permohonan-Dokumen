<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectDocumentController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/', fn () => response()->json([
        'name' => config('app.name'),
        'version' => '1.0.0',
    ]));

    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:10,1');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::prefix('auth')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });

        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view');

        Route::get('references', [ReferenceController::class, 'index']);

        Route::get('projects/export/excel', [ExportController::class, 'excel'])
            ->middleware('permission:projects.export');
        Route::get('projects/export/pdf', [ExportController::class, 'pdf'])
            ->middleware('permission:projects.export');
        Route::get('projects/{project}/pdf', [ExportController::class, 'projectPdf'])
            ->middleware('permission:projects.view');

        Route::apiResource('projects', ProjectController::class)->only([
            'index', 'store', 'show', 'update', 'destroy',
        ]);

        Route::post('projects/{project}/submit', [ProjectController::class, 'submit'])
            ->middleware('permission:projects.submit');
        Route::post('projects/{project}/start-review', [ProjectController::class, 'startReview'])
            ->middleware('permission:reviews.create');
        Route::get('projects/{project}/history', [ProjectController::class, 'history']);

        Route::post('projects/{project}/documents', [ProjectDocumentController::class, 'store'])
            ->middleware('permission:documents.upload');
        Route::delete('projects/{project}/documents/{document}', [ProjectDocumentController::class, 'destroy'])
            ->middleware('permission:documents.upload');
        Route::get('projects/{project}/documents/{document}/download', [ProjectDocumentController::class, 'download'])
            ->name('projects.documents.download');

        Route::get('reviews', [ReviewController::class, 'index'])
            ->middleware('permission:reviews.view');
        Route::post('reviews', [ReviewController::class, 'store'])
            ->middleware('permission:reviews.create');
    });
});
