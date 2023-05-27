<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CCD\QuestionController;
use App\Http\Controllers\Home\ExamController;
use App\Http\Controllers\Home\CallbackController;
use App\Http\Controllers\Home\ComplaintController;
use App\Http\Controllers\Home\AnnouncementController;
use App\Http\Controllers\Home\WebhookController;

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

// Route::any('/offline-app/activate', [OfflineAppController::class, 'apiActivate'])->name('api.offline-app.activate');
// Route::any('/offline-app/init-license-payment', [OfflineAppController::class, 'apiInitLicensePayment'])->name('api.offline-app.init-license-payment');
// Route::any('/offline-app/verify-payment-reference', [OfflineAppController::class, 'apiVerifyLicensePayment'])->name('api.offline-app.verify-payment');


/*
Route::any('/feedback/store', [ComplaintController::class, 'apiStoreComplaint'])->name('api.feedback.store');
Route::any('/announcements/{id?}', [AnnouncementController::class, 'apiIndex'])->name('api.announcement.index');

Route::any('/webhook/paystack', [WebhookController::class, 'paystackWebhook']);
Route::any('/webhook/rave', [WebhookController::class, 'raveWebhook']);
Route::any('/webhook/monnify', [WebhookController::class, 'monnifyWebhook']);

Route::post('/home/cheetahpay/callback', [CallbackController::class, 'cheetahpayCallback'])->name('cheetahpay-callback');
*/

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

Route::any('/login', [\App\Http\Controllers\Auth\LoginController::class, 'apiLogin']);
Route::any('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'apiRegister']);

Route::middleware('auth:sanctum')->get('/rough', function (Request $request) {
// Route::get('/rough', function (Request $request) {
    
    $user = Auth::guard('sanctum')->user();
    
    die(json_encode($user));
//     return $request->user();
});

