<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;
use App\Http\Controllers\Institutions\Exams\ExamAttempts;

Route::get(
  'events/offline-cbt/setup-guide',
  [Web\Exams\EventController::class, 'offlineCbtSetupGuide']
)->name('events.offline-cbt.setup-guide');

Route::get('events/{event}/download', [Web\Exams\EventController::class, 'download'])
    ->name('events.download');
Route::resource('/events', Web\Exams\EventController::class);

Route::get(
  'events/{event}/attempt-activity',
  ExamAttempts\ActivitySummaryController::class
)->name('events.attempt-activity');

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
Route::post('/{exam}/exam-courseables/{examCourseable}/evaluate-theory', [Web\Exams\ExamCourseableController::class, 'evaluateTheory'])
    ->name('exam-courseables.evaluate-theory');
Route::resource('/{exam}/exam-courseables', Web\Exams\ExamCourseableController::class)
    ->except(['edit', 'update', 'destroy']);

Route::post(
  'exam-attempts/{exam}/answers',
  ExamAttempts\RecordAttemptController::class
)->name('exam-attempts.answers.store');
Route::post(
  'exam-attempts/{exam}/ping',
  ExamAttempts\PingAttemptController::class
)->name('exam-attempts.ping');
    
Route::post('events/{event}/transfer-results', Web\Exams\TransferEventResultController::class)
->name('events.transfer-results');

Route::get(
  'events/{event}/transfer-results-multiple',
  [Web\Exams\TransferEventResultMultipleController::class, 'create']
)->name('events.transfer-results-multiple');
Route::post(
  'events/{event}/transfer-results-multiple',
  [Web\Exams\TransferEventResultMultipleController::class, 'store']
)->name('events.transfer-results-multiple.store');
