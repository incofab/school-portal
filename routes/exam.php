<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;

Route::resource('/events', Web\Exams\EventController::class);

Route::delete('event-courseables/{eventCourseable}/delete', [Web\Exams\EventCourseableController::class, 'destroy'])
    ->name('event-courseables.destroy');
Route::resource('/{event}/event-courseables', Web\Exams\EventCourseableController::class)
    ->except(['show', 'edit', 'update', 'destroy']);

Route::delete('exams/{exam}/delete', [Web\Exams\ExamController::class, 'destroy'])
    ->name('exams.destroy');
Route::resource('/{event}/exams', Web\Exams\ExamController::class)
    ->except(['show', 'edit', 'update', 'destroy']);

Route::delete('exam-courseables/{examCourseable}/delete', [Web\Exams\ExamCourseableController::class, 'destroy'])
    ->name('exam-courseables.destroy');
Route::resource('/{exam}/exam-courseables', Web\Exams\ExamCourseableController::class)
    ->except(['show', 'edit', 'update', 'destroy']);
    
Route::delete('events/{event}/transfer-results', Web\Exams\TransferEventResultController::class)
->name('events.transfer-results');