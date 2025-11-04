<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Home as Home;
use App\Http\Controllers as Web;
use App\Http\Controllers\Institutions\Exams\External as External;
use App\Http\Controllers\Institutions\Admissions as Admissions;

Route::get('dummy1', function () {
    $user = \App\Models\User::where('email', 'guard2@email.com')->first();
    $res = \App\Core\MonnifyHelper::make()->getReservedAccounts($user);

    // $res = \App\Core\MonnifyHelper::make()->listBanks();
    dd($res->toArray());
    // dd('skdksdk');
    return Mail::to('incofabikenna@gmail.com')->send(new \App\Mail\InstitutionMessageMail(
        \App\Models\Institution::first(),
        'Subject of this email',
        'This is a test message for my testing',
    ));
    die('Dummy page');
});

Route::get('/no-mid-term/{instUuid}', function ($instUuid) {
    $inst = \App\Models\Institution::where('uuid', $instUuid)->firstOrFail();
    $b = [
        'academic_session_id' => 4,
        'term' => \App\Enums\TermType::Third,
        'institution_id' => $inst->id,
        'for_mid_term' => true,
    ];
    dd($inst->termResults()->where($b)->count());
    $inst->termResults()
      ->where($b)
      ->update(['for_mid_term' => false]);
    \App\Models\CourseResult::query()
      ->where($b)
      ->update(['for_mid_term' => false]);
    \App\Models\CourseResultInfo::query()
      ->where($b)
      ->update(['for_mid_term' => false]);
    \App\Models\ClassResultInfo::query()
      ->where($b)
      ->update(['for_mid_term' => false]);
      dd('Done');
});

Route::get('/activate-result/{instUuid}', function ($instUuid) {
    $inst = \App\Models\Institution::where('uuid', $instUuid)->firstOrFail();
    $termResults = \App\Models\TermResult::query()
      ->where('institution_id', $inst->id)
      ->where('for_mid_term', false)
      ->where('term', \App\Enums\TermType::Second)
      ->where('academic_session_id', 4)
      ->activated(false)
    //   ->update(['is_activated' => true]);
      ->count();
    return "Result $termResults";
});

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

Route::get('banks/search', [Web\BankController::class, 'search'])->name('banks.search');
Route::any('bank-accounts/validate', [Web\BankController::class, 'validateBankAccount'])->name('bank-accounts.validate');
Route::get('institutions/search', Web\SearchInstitutionController::class)
    ->name('institutions.search');
Route::get('academic-sessions/search', [Web\AcademicSessionController::class, 'search'])
    ->name('academic-sessions.search');
Route::get('/expense-categories/search', [Web\Institutions\Expenses\ExpenseCategoryController ::class, 'search'])
    ->name('expense-categories.search');
Route::get('/salary-types/search', [Web\Institutions\Payrolls\SalaryTypesController::class, 'search'])
    ->name('salary-types.search');
Route::get('/payroll-adjustment-types/search', [Web\Institutions\Payrolls\PayrollAdjustmentTypesController::class, 'search'])
    ->name('payroll-adjustment-types.search');
Route::post('activate-result', [Web\TermResultActivationController::class, 'store'])
    ->name('activate-term-result.store');
    
Route::get('error', [Home\HomeController::class, 'error'])->name('home.error');

Route::get('/institutions/{institution}/admission-forms/search', [Admissions\AdmissionFormController::class, 'search'])->name('institutions.admission-forms.search');

Route::group(['prefix' => '{institution}/admissions/', 'as' => 'institutions.'], function () {
    Route::get('apply', [Admissions\AdmissionApplicationController::class, 'create'])
        ->name('admissions.create');
    Route::post('apply', [Admissions\AdmissionApplicationController::class, 'store'])
        ->name('admissions.store');
    // Route::get('{admissionApplication}/application-success', [Admissions\AdmissionApplicationController::class, 'successMessage'])
    //     ->name('admissions.success');
    Route::get('letter/{student}', [Admissions\AdmissionApplicationController::class, 'admissionLetter'])
        ->name('admissions.letter');
    Route::get('/{admissionApplication}/preview', [Admissions\AdmissionApplicationController::class, 'previewAdmissionApplication'])
        ->name('admission-applications.preview');
    Route::any('/admission-forms/{admissionForm}/buy/{admissionApplication}', [Admissions\AdmissionApplicationController::class, 'buyAdmissionForm'])
        ->name('admission-forms.buy');
});

Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [Web\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Web\AuthController::class, 'login'])->name('login.store');
    Route::get('register/{partner?}', [Web\InstitutionRegistrationRequestController::class, 'create'])->name('registration-requests.create');
    Route::post('register/{partner?}', [Web\InstitutionRegistrationRequestController::class, 'store'])->name('registration-requests.store');

    Route::get('partner-registration', [Web\PartnerRegistrationRequestController::class, 'create'])->name('partner-registration-requests.create');
    Route::post('partner-registration', [Web\PartnerRegistrationRequestController::class, 'store'])->name('partner-registration-requests.store');

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
    Route::put('users/bvn-nin/update', [Web\Users\UserController::class, 'updateBvnNin'])
        ->name('users.bvn-nin.update');

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

Route::any('/monnify/callback', [Home\MonnifyController::class, 'callback'])->name('monnify.callback');
Route::any('/monnify/verify-reference', [Home\MonnifyController::class, 'verifyReference'])->name('monnify.verify-reference');
Route::any('/monnify/webhook', [Home\MonnifyController::class, 'webhook'])->name('monnify.webhook');

Route::any('/payment-point/webhook', [Home\PaymentPointController::class, 'webhook'])->name('payment-point.webhook');

Route::get('/app-not-activated', External\NotActivatedErrorController::class);

Route::get('/student/exam-login', [External\ExamExternalController::class, 'studentExamLoginCreate'])->name('student.exam.login.create');
Route::post('/student/exam-login', [External\ExamExternalController::class, 'studentExamLoginStore'])->name('student.exam.login.store');

Route::get('/admissions/exam-login', [External\ExamExternalController::class, 'admissionExamLoginCreate'])->name('admissions.exam.login.create');
Route::post('/admissions/exam-login', [External\ExamExternalController::class, 'admissionExamLoginStore'])->name('admissions.exam.login.store');

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
