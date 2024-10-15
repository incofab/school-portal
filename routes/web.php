<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Home as Home;
use App\Http\Controllers as Web;
use App\Http\Controllers\Institutions\Exams\External as External;
use App\Models\Institution;

Route::get('pdf-result/{student}', [Web\TermResultActivationController::class, 'showPdfResult'])
    ->name('show-pdf-result');
Route::get(
    '{institution}/students/signed-result-sheet/{student}/{classification}/{academicSession}/{term}/{forMidTerm}',
    [Web\Institutions\Students\ViewResultSheetController::class, 'viewResultSigned']
)->name('institutions.students.result-sheet.signed');
Route::any(
    'pdf-bridge',
    [Web\Institutions\Students\ViewResultSheetController::class, 'pdfBridge']
)->name('pdf-bridge');
Route::any(
    'pdf-bridge-download',
    [Web\Institutions\Students\ViewResultSheetController::class, 'pdfBridgeDownload']
)->name('pdf-bridge-download');

Route::get('/dummy1', function () {
    // Top Notchers activate result
    $institution = \App\Models\Institution::where('uuid', '9a668567-156c-4f8f-a6c6-dbd6443c34ac')->first();
    $result = \App\Models\TermResult::query()->where('institution_id', $institution->id)->where('is_activated', false)
        ->update(['is_activated' => true]);
    dd("Result = $result");

    // Checks Wisegate result files
    $instUsers = \App\Models\InstitutionUser::where('institution_id', 1)
        ->where('role', \App\Enums\InstitutionUserType::Student)
        ->with('student.user', 'student.classification')
        ->get();

    $i = 0;
    foreach ($instUsers as $key => $instUser) {
        if (File::exists(public_path("wisegate/{$instUser->student->code}.pdf"))) {
            continue;
        }
        echo "Name = {$instUser->student->user->full_name}, Code={$instUser->student->code}, Class={$instUser->student->classification->title} <br><br>";
        $i++;
    }

    dd(',ksdmksdmds = ' . $i);
});

Route::get('institutions/search', Web\SearchInstitutionController::class)
    ->name('institutions.search');
Route::get('academic-sessions/search', [Web\AcademicSessionController::class, 'search'])
    ->name('academic-sessions.search');
Route::post('activate-result', [Web\TermResultActivationController::class, 'store'])
    ->name('activate-term-result.store');

Route::group(['prefix' => '{institution}/admissions/'], function () {
    // Route::get('apply', [Web\Institutions\AdmissionApplicationController::class, 'create'])
    //     ->name('institutions.admissions.create');
    Route::post('apply', [Web\Institutions\AdmissionApplicationController::class, 'store'])
        ->name('institutions.admissions.store');
    Route::get('{admissionApplication}/application-success', [Web\Institutions\AdmissionApplicationController::class, 'successMessage'])
        ->name('institutions.admissions.success');
    // Route::get('letter/{student}', [Web\Institutions\AdmissionApplicationController::class, 'admissionLetter'])
    //     ->name('institutions.admissions.letter');
});

Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [Web\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Web\AuthController::class, 'login'])->name('login.store');
    Route::get('register/{partner?}', [Web\InstitutionRegistrationRequestController::class, 'create'])->name('registration-requests.create');
    Route::post('register/{partner?}', [Web\InstitutionRegistrationRequestController::class, 'store'])->name('registration-requests.store');
    Route::get('registration-completed/{registrationRequest}', [Web\InstitutionRegistrationRequestController::class, 'registrationCompleted'])
        ->name('registration-requests.completed-message');

    Route::get('result', [Web\TermResultActivationController::class, 'create'])->name('activate-term-result.create');

    Route::get('student/login', [Web\StudentAuthController::class, 'showLogin'])->name('student-login');
    Route::post('student/login', [Web\StudentAuthController::class, 'login'])->name('student-login.store');

    Route::get('forgot-password', [Web\AuthController::class, 'showForgotPassword'])
        ->name('forgot-password');
    Route::post('forgot-password', [Web\AuthController::class, 'forgotPassword'])
        ->name('forgot-password.store');
    Route::get('reset-password/{token}', [Web\AuthController::class, 'showResetPassword'])
        ->name('password.reset');
    Route::post('reset-password', [Web\AuthController::class, 'resetPassword'])
        ->name('password.update');
});

Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', [Web\Users\UserController::class, 'index'])->name('user.dashboard');

    Route::get('users/change-password', [Web\Users\ChangeUserPasswordController::class, 'edit'])
        ->name('users.password.edit');
    Route::put('users/change-password', [Web\Users\ChangeUserPasswordController::class, 'update'])
        ->name('users.password.update');

    Route::group(['middleware' => ['manager']], function () {
        Route::get('impersonate/users/{user}', Web\Impersonate\ImpersonateUserController::class)->name('users.impersonate');
        Route::get('impersonate/institutions/{institution}', Web\Impersonate\ImpersonateInstitutionController::class)->name('institutions.impersonate');
    });
    Route::delete('impersonate/{user}', Web\Impersonate\StopImpersonatingUserController::class)->name('users.impersonate.destroy');

    Route::any('/logout', [Web\AuthController::class, 'logout'])->name('logout');
});

Route::get('/{institution}/my-exam/test-display/{exam:exam_no}', Web\Institutions\Exams\ExamPage\TestDisplayExamPageController::class);
Route::group(['prefix' => '{institution}/my-exam/'], function () {
    Route::get('/login', Web\Institutions\Exams\ExamPage\ExamLoginController::class)
        ->name('institutions.exams.login');
    Route::get('/display/{exam:exam_no}', Web\Institutions\Exams\ExamPage\DisplayExamPageController::class)
        ->name('institutions.display-exam-page');
    Route::post('/pause/{exam}', Web\Institutions\Exams\ExamPage\PauseExamController::class)
        ->name('institutions.pause-exam');
    Route::post('/end/{exam}', Web\Institutions\Exams\ExamPage\EndExamController::class)
        ->name('institutions.end-exam');
    // Route::get('/exam/completed/{examNo?}', [\App\Http\Controllers\Exam\ExamController::class, 'examCompleted'])->name('home.exam.completed');
    // Route::get('/exam/view-result-form', [\App\Http\Controllers\Exam\ExamController::class, 'viewResultForm'])->name('home.exam.view-result-form');
    // Route::get('/exam/view-result', [\App\Http\Controllers\Exam\ExamController::class, 'viewResult'])->name('home.exam.view-result');
});

Route::get('/', [Home\HomeController::class, 'index'])->name('home');
Route::any('/privacy-policy', [Home\HomeController::class, 'privacyPolicy'])->name('privacy-policy');
Route::any('/paystack/callback', [Home\PaystackController::class, 'callback'])->name('paystack.callback');
Route::any('/paystack/verify-reference', [Home\PaystackController::class, 'verifyReference'])->name('paystack.verify-reference');
Route::any('/paystack/webhook', [Home\PaystackController::class, 'webhook'])->name('paystack.webhook');

Route::get('/app-not-activated', External\NotActivatedErrorController::class);
Route::group(['prefix' => 'external/{institution}/'], function () {
    Route::post('/get-user-token', External\GetUserTokenController::class);
    Route::get('/home', External\HomeExternalController::class)
        ->name('institutions.external.home');

    Route::get('/token-user/{tokenUser}/edit', [External\UpdateTokenUserProfileController::class, 'edit'])
        ->name('institutions.external.token-users.edit');
    Route::put('/token-user/{tokenUser}/update', [External\UpdateTokenUserProfileController::class, 'update'])
        ->name('institutions.external.token-users.update');

    Route::get('/events/{event}', External\DisplayEventController::class)
        ->name('institutions.external.events.show');

    Route::get('/{event}/exams/create', [External\ExamExternalController::class, 'create'])
        ->name('institutions.external.exams.create');
    Route::post('/{event}/exams', [External\ExamExternalController::class, 'store'])
        ->name('institutions.external.exams.store');
    Route::get('/exam-result/{exam:exam_no}', Web\Institutions\Exams\ExamPage\ExamResultController::class)
        ->name('institutions.external.exam-result');
    Route::get('/leader-board/{event?}', External\ShowLeaderBoardController::class)
        ->name('institutions.external.leader-board');
});
