<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;


Route::get('/attendances/create', [Web\Attendance\AttendanceController::class, 'create'])->name('attendances.create');
Route::apiResource('/attendances', Web\Attendance\AttendanceController::class);