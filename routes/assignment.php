<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;

Route::resource('/assignments', Web\Assignments\AssignmentController::class);
Route::resource('/assignment-submissions', Web\Assignments\AssignmentSubmissionController::class);

Route::post('/assignment-submissions/{assignmentSubmission}/score', [Web\Assignments\AssignmentSubmissionController::class, 'score'])
    ->name('assignment-submission.score');
Route::get('/assignment-submissions/{assignment}/submissions', [Web\Assignments\AssignmentSubmissionController::class, 'list'])
    ->name('assignment-submission.submissions');