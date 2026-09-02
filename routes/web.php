<?php

use App\Http\Controllers\WeeklyReportController;
use App\Http\Controllers\PrintPlanOfActionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->group(function () {
    Route::get('/weekly-reports/{weeklyReport}', [WeeklyReportController::class, 'show'])->name('weekly-reports.show');
    Route::get('/poa/recap', [PrintPlanOfActionController::class, 'recap'])->name('poa.recap');
});
