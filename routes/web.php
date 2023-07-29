<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers as Web;

Route::get('pdf-result/{student}', [Web\TermResultActivationController::class, 'showPdfResult'])
    ->name('show-pdf-result');

Route::get('/dummy1', function ()
{
    $instUsers = \App\Models\InstitutionUser::where('institution_id', 1)
    ->where('role', \App\Enums\InstitutionUserType::Student)
    ->with('student.user', 'student.classification')
    ->get();

    $i = 0;
    foreach ($instUsers as $key => $instUser) {
        if(File::exists(public_path("wisegate/{$instUser->student->code}.pdf"))){
            continue;
        }
        echo "Name = {$instUser->student->user->full_name}, Code={$instUser->student->code}, Class={$instUser->student->classification->title} <br><br>";
        $i++;
    }
    
    dd(',ksdmksdmds = '.$i);
});

Route::get('institutions/search', Web\SearchInstitutionController::class)
    ->name('institutions.search');
Route::get('academic-sessions/search', [Web\AcademicSessionController::class, 'search'])
    ->name('academic-sessions.search');
Route::get('result', [Web\TermResultActivationController::class, 'create'])
    ->name('activate-term-result.create');
Route::post('activate-result', [Web\TermResultActivationController::class, 'store'])
    ->name('activate-term-result.store');

Route::group(['prefix' => '{institution}/admissions/'], function () {
    Route::get('apply', [Web\Institutions\AdmissionApplicationController::class, 'create'])
        ->name('institutions.admissions.create');
    Route::post('apply', [Web\Institutions\AdmissionApplicationController::class, 'store'])
        ->name('institutions.admissions.store');
    Route::get('{admissionApplication}/application-success', [Web\Institutions\AdmissionApplicationController::class, 'successMessage'])
        ->name('institutions.admissions.success');
});

Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [Web\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Web\AuthController::class, 'login'])->name('login.store');
    Route::get('register', [Web\RegistrationController::class, 'create'])->name('register.create');
    Route::post('register', [Web\RegistrationController::class, 'store'])->name('register.store');
    
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
    
    Route::get('impersonate/{user}', Web\Users\ImpersonateUserController::class)->name('users.impersonate');
    Route::delete('impersonate/{user}', Web\Users\StopImpersonatingUserController::class)->name('users.impersonate.destroy');
    
    Route::any('/logout', [Web\AuthController::class, 'logout'])->name('logout');
});






Route::get('/', [HomeController::class, 'index'])->name('home');
Route::any('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');

Route::get('/exam/start/{examNo?}', [\App\Http\Controllers\Exam\ExamController::class, 'startExam'])->name('home.exam.start');
Route::get('/exam/completed/{examNo?}', [\App\Http\Controllers\Exam\ExamController::class, 'examCompleted'])->name('home.exam.completed');
Route::get('/exam/view-result-form', [\App\Http\Controllers\Exam\ExamController::class, 'viewResultForm'])->name('home.exam.view-result-form');
Route::get('/exam/view-result', [\App\Http\Controllers\Exam\ExamController::class, 'viewResult'])->name('home.exam.view-result');
