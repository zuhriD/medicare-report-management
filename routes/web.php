<?php

use App\Http\Controllers\AttendanceRecapExportController;
use App\Http\Controllers\DailyAttendanceAuditExportController;
use App\Http\Controllers\DailyReportPrintController;
use App\Http\Controllers\WeeklyReportController;
use App\Http\Controllers\PrintPlanOfActionController;
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
    Route::get('/poa/recap', [PrintPlanOfActionController::class, 'recap'])->name('poa.recap');
    Route::get('/reports/attendance-recap/export', [AttendanceRecapExportController::class, 'export'])->name('attendance-recap.export');
    Route::get('/reports/daily-attendance-audit/export', [DailyAttendanceAuditExportController::class, 'export'])->name('daily-attendance-audit.export');
});


