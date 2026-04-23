<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReferentielController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ReferentielModelController;
use App\Http\Controllers\ModuleValidationController;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');


Route::get('/settings', [ReferentielController::class, 'index'])->name('settings.index');
Route::get('/settings/modules', [ModuleController::class, 'settingsIndex'])->name('settings.modules');
Route::post('/settings/upload', [ReferentielController::class, 'upload'])->name('settings.upload');
Route::put('/settings/{referentiel}', [ReferentielController::class, 'update'])->name('settings.update');
Route::delete('/settings/{referentiel}', [ReferentielController::class, 'destroy'])->name('settings.delete');
Route::get('/settings/{referentiel}/modules', [ReferentielController::class, 'getModules'])->name('settings.modules.get');
Route::post('/settings/{referentiel}/modules', [ReferentielController::class, 'storeModule'])->name('settings.modules.store');
Route::put('/settings/modules/{module}', [ReferentielController::class, 'updateModule'])->name('settings.modules.update');
Route::post('/settings/{referentiel}/extract', [ReferentielController::class, 'extract'])->name('settings.extract');

// Routes pour les modèles de référentiels
Route::get('/referentiel-models', [ReferentielModelController::class, 'index'])->name('referentiel-models.index');
Route::get('/referentiel-models/{modelKey}/extract-columns', [ReferentielModelController::class, 'extractColumns'])->name('referentiel-models.extract-columns');
Route::get('/referentiel-models/validation', [ReferentielModelController::class, 'validation'])->name('referentiel-models.validation');

// Routes pour la validation de modules
Route::post('/module-validation/validate-columns', [ModuleValidationController::class, 'validateColumns'])->name('module-validation.validate-columns');
Route::post('/module-validation/compare-models', [ModuleValidationController::class, 'compareModels'])->name('module-validation.compare-models');
Route::get('/module-validation/{modelKey}/report', [ModuleValidationController::class, 'generateValidationReport'])->name('module-validation.report');


