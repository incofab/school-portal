<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CCD\QuestionController;
use App\Http\Controllers\Institutions\Attendance\AttendanceController;
use App\Http\Controllers\API\OfflineMock;

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

Route::middleware(['auth:sanctum', 'institution.user'])->post('/{institution}/attendance', [AttendanceController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']); // Login route for API

Route::middleware('auth:sanctum')->any('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => []], function () {

    // Route::any('/exam/pause', [\App\Http\Controllers\Exam\ExamController::class, 'pauseExam']);
    // Route::any('/exam/end', [\App\Http\Controllers\Exam\ExamController::class, 'endExam']);
    // Route::any('/exam/submit', [\App\Http\Controllers\Exam\ExamController::class, 'submitExam']);

    // Route::any('/institution/event/index', [\App\Http\Controllers\API\EventController::class, 'index']);
    // Route::any('/institution/event/download', [\App\Http\Controllers\API\EventController::class, 'downloadEventContent']);
    // Route::any('/institution/event/upload', [\App\Http\Controllers\API\EventController::class, 'uploadEventResult']);

});

Route::group(['middleware' => []], function () {
    Route::any('/ccd/institution/{institution_id}/question/create/{sessionId}', [QuestionController::class, 'apiCreate'])->name('api.ccd.question.create');
});

Route::group(['middleware' => [], 'prefix' => '/offline-mock/institutions/{institution:code}', 'as' => 'offline-mock.'], function () {
    Route::any('show-institution', [OfflineMock\InstitutionController::class, 'show'])->name('institutions.show');
    
    Route::any('events', [OfflineMock\EventController::class, 'index'])->name('events.index');
    Route::any('events/{event}/show', [OfflineMock\EventController::class, 'show'])->name('events.show');
    Route::any('events/{event}/deep-show', [OfflineMock\EventController::class, 'deepShow'])->name('events.deep-show');
    Route::any('exams/upload', [OfflineMock\ExamController::class, 'uploadEventResult'])->name('exams.upload');
});