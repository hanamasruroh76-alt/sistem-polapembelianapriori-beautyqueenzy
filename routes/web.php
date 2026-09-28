<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;

Route::get('/', function () {
    return redirect()->route('apriori.dashboard');
})->name('welcome');

Route::get('/apriori', [DashboardController::class, 'index'])->name('apriori.dashboard');
Route::post('/apriori/filter', [DashboardController::class, 'filter'])->name('apriori.filter');
Route::get('/apriori/recommendation/pdf', [DashboardController::class, 'downloadRecommendationPdf'])->name('apriori.recommendation.pdf');

Route::post('/import', [ImportController::class, 'store'])->name('import.store');
Route::post('/import/clear', [ImportController::class, 'clear'])->name('import.clear');
Route::get('/import/data', [ImportController::class, 'data'])->name('import.data');
Route::get('/import/template/download', [ImportController::class, 'downloadTemplate'])->name('import.downloadTemplate');
