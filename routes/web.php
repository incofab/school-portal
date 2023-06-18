<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers as Web;

Route::group(['middleware' => ['institution.user']], function () {
    Route::get('/dummy1', function ()
    {
        dd(',ksdmksdmds-12');
    });
});

Route::get('institutions/search', Web\SearchInstitutionController::class)
    ->name('institutions.search');
Route::get('academic-sessions/search', [Web\AcademicSessionController::class, 'search'])
    ->name('academic-sessions.search');
Route::get('result', [Web\TermResultActivationController::class, 'create'])
    ->name('activate-term-result.create');
Route::post('activate-result', [Web\TermResultActivationController::class, 'store'])
    ->name('activate-term-result.store');

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

Route::any('/logout', '\App\Http\Controllers\Auth\LoginController@logout')->name('logout');

Route::group(['middleware' => ['auth']], function () {
  Route::get('/dashboard', [Web\Users\UserController::class, 'index'])->name('user.dashboard');
    
  Route::get('users/change-password', [Web\Users\ChangeUserPasswordController::class, 'edit'])
  ->name('users.password.edit');
  Route::put('users/change-password', [Web\Users\ChangeUserPasswordController::class, 'update'])
  ->name('users.password.update');

  Route::get('impersonate/{user}', Web\Users\ImpersonateUserController::class)->name('users.impersonate');
  Route::delete('impersonate/{user}', Web\Users\StopImpersonatingUserController::class)->name('users.impersonate.destroy');
});






Route::get('/', [HomeController::class, 'index'])->name('home');
Route::any('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');

Route::get('/exam/start/{examNo?}', [\App\Http\Controllers\Exam\ExamController::class, 'startExam'])->name('home.exam.start');
Route::get('/exam/completed/{examNo?}', [\App\Http\Controllers\Exam\ExamController::class, 'examCompleted'])->name('home.exam.completed');
Route::get('/exam/view-result-form', [\App\Http\Controllers\Exam\ExamController::class, 'viewResultForm'])->name('home.exam.view-result-form');
Route::get('/exam/view-result', [\App\Http\Controllers\Exam\ExamController::class, 'viewResult'])->name('home.exam.view-result');
