<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CCD\QuestionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->any('/user', function (Request $request) {
    
    return $request->user();
    
});

Route::group(['middleware' => []], function() {
    
    Route::any('/exam/pause', [\App\Http\Controllers\Exam\ExamController::class, 'pauseExam']);
    Route::any('/exam/end', [\App\Http\Controllers\Exam\ExamController::class, 'endExam']);
    Route::any('/exam/submit', [\App\Http\Controllers\Exam\ExamController::class, 'submitExam']);

    Route::any('/institution/event/index', [\App\Http\Controllers\API\EventController::class, 'index']);
    Route::any('/institution/event/download', [\App\Http\Controllers\API\EventController::class, 'downloadEventContent']);
    Route::any('/institution/event/upload', [\App\Http\Controllers\API\EventController::class, 'uploadEventResult']);
    
});

Route::group(['middleware' => []], function() {
    Route::any('/ccd/institution/{institution_id}/question/create/{sessionId}', [QuestionController::class, 'apiCreate'])->name('api.ccd.question.create');
});

