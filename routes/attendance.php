<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;


Route::get('/attendances/create', [Web\Attendance\AttendanceController::class, 'create'])->name('attendances.create');
Route::get('/attendances/students', [Web\Attendance\AttendanceController::class, 'students'])->name('attendances.students');
Route::get('/attendances/class-register', [Web\Attendance\AttendanceController::class, 'classRegister'])->name('attendances.class-register');
Route::get('/attendances/class-register/view', [Web\Attendance\AttendanceController::class, 'classRegisterView'])->name('attendances.class-register.view');
Route::post('/attendances/bulk', [Web\Attendance\AttendanceController::class, 'bulkStore'])->name('attendances.bulk-store');
Route::apiResource('/attendances', Web\Attendance\AttendanceController::class);
Route::get('/attendance-reports', [Web\Attendance\StudentAttendanceReportController::class, 'index'])
  ->name('attendance-reports.index');
Route::post('/attendance-reports/retrieve', [Web\Attendance\StudentAttendanceReportController::class, 'report'])
  ->name('attendance-reports.retrieve');
