<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionnaireImportController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
// Route untuk edit assessment
Route::get('/assessment/{id}/edit', [AssessmentController::class, 'edit'])->name('assessment.edit');
Route::put('/assessment/{id}', [AssessmentController::class, 'update'])->name('assessment.update');
// Questionnaire Routes
Route::prefix('questionnaire')->group(function () {

    Route::get('/', [QuestionnaireController::class, 'index'])
        ->name('questionnaire.index');

    // =============================
    // IMPORT (WAJIB DI SINI)
    // =============================
    Route::post('/import/preview',
        [QuestionnaireImportController::class, 'preview']
    )->name('questionnaire.import.preview');

    Route::post('/import',
        [QuestionnaireImportController::class, 'import']
    )->name('questionnaire.import');

    // =============================
    // QUESTIONS
    // =============================
    Route::post('/categories', [QuestionnaireController::class, 'storeCategory'])
        ->name('questionnaire.categories.store');

    Route::post('/questions', [QuestionnaireController::class, 'storeQuestion'])
        ->name('questionnaire.questions.store');

    Route::put('/questions/{question}', [QuestionnaireController::class, 'updateQuestion'])
        ->name('questionnaire.questions.update');

    Route::delete('/questions/{question}', [QuestionnaireController::class, 'destroyQuestion'])
        ->name('questionnaire.questions.destroy');

    Route::post('/questions/order', [QuestionnaireController::class, 'updateOrder'])
        ->name('questionnaire.questions.order');

    // =============================
    // EDIT ALL (FIX URL!)
    // =============================
    Route::get('/edit-all', [QuestionnaireController::class, 'editAll'])
        ->name('questionnaire.editAll');

    Route::put('/update-all', [QuestionnaireController::class, 'updateAll'])
        ->name('questionnaire.updateAll');
});

Route::get('/questionnaire/categories', [CategoryController::class, 'create'])->name('categories.create');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
    ->name('categories.destroy');


Route::get('/assessment/export-report', [AssessmentController::class, 'exportReport'])
    ->name('assessment.export.report');

// Assessment Routes
Route::prefix('assessment')->group(function () {
    Route::get('/', [AssessmentController::class, 'index'])->name('assessment.index');
    Route::get('/create', [AssessmentController::class, 'create'])->name('assessment.create');
    Route::post('/', [AssessmentController::class, 'store'])->name('assessment.store');
    Route::get('/{assessment}', [AssessmentController::class, 'show'])
        ->whereNumber('assessment')
        ->name('assessment.show');
    Route::get('/{assessment}/export', [AssessmentController::class, 'export'])->name('assessment.export');
    Route::get('/{assessment}/preview', [AssessmentController::class, 'previewExport'])->name('assessment.preview');
    Route::post('/{assessment}/import', [AssessmentController::class, 'import'])->name('assessment.import');
    Route::delete('/{assessment}', [AssessmentController::class, 'destroy'])->name('assessment.destroy');

});

Route::get('/questionnaire/export', [QuestionnaireController::class, 'export'])->name('questionnaire.export');


Route::post('/dashboard/export-ppt', [DashboardController::class, 'exportPpt'])
    ->name('dashboard.export.ppt');
