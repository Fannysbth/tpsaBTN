<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\AssessmentController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

// Questionnaire Routes
Route::prefix('questionnaire')->group(function () {
    Route::get('/', [QuestionnaireController::class, 'index'])->name('questionnaire.index');
    Route::post('/categories', [QuestionnaireController::class, 'storeCategory'])->name('questionnaire.categories.store');
    Route::post('/questions', [QuestionnaireController::class, 'storeQuestion'])->name('questionnaire.questions.store');
    Route::put('/questions/{question}', [QuestionnaireController::class, 'updateQuestion'])->name('questionnaire.questions.update');
    Route::delete('/questions/{question}', [QuestionnaireController::class, 'destroyQuestion'])->name('questionnaire.questions.destroy');
    Route::post('/questions/order', [QuestionnaireController::class, 'updateOrder'])->name('questionnaire.questions.order');
    // Page edit all questions
Route::get('/questionnaire/edit-all', [QuestionnaireController::class, 'editAll'])
    ->name('questionnaire.editAll');

// Update all questions sekaligus
Route::put('/questionnaire/update-all', [QuestionnaireController::class, 'updateAll'])
    ->name('questionnaire.updateAll');

});


// Assessment Routes
Route::prefix('assessment')->group(function () {
    Route::get('/', [AssessmentController::class, 'index'])->name('assessment.index');
    Route::get('/create', [AssessmentController::class, 'create'])->name('assessment.create');
    Route::post('/', [AssessmentController::class, 'store'])->name('assessment.store');
    Route::get('/{assessment}', [AssessmentController::class, 'show'])->name('assessment.show');
    Route::get('/{assessment}/export', [AssessmentController::class, 'export'])->name('assessment.export');
    Route::get('/{assessment}/preview', [AssessmentController::class, 'previewExport'])->name('assessment.preview');
    Route::post('/{assessment}/import', [AssessmentController::class, 'import'])->name('assessment.import');
    Route::post('/{assessment}/send-email', [AssessmentController::class, 'sendEmail'])->name('assessment.send-email');
});