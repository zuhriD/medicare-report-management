<?php

use App\Http\Controllers\DailyReportPrintController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/daily-reports/print', [DailyReportPrintController::class, 'show'])->name('daily-reports.print');
    Route::get('/weekly-reports/{weeklyReport}', [WeeklyReportController::class, 'show'])->name('weekly-reports.show');
});


