<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;


Route::get('/attendances/create', [Web\Attendance\AttendanceController::class, 'create'])->name('attendances.create');
Route::apiResource('/attendances', Web\Attendance\AttendanceController::class);
Route::get('/attendance-reports', [Web\Attendance\StudentAttendanceReportController::class, 'index'])
  ->name('attendance-reports.index');
Route::post('/attendance-reports/retrieve', [Web\Attendance\StudentAttendanceReportController::class, 'report'])
  ->name('attendance-reports.retrieve');
