<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers as Web;

// Auth::routes();
Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [Web\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Web\AuthController::class, 'login'])->name('login.store');
    Route::resource('register', Web\RegistrationController::class)
        ->only(['create', 'store']);
  
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








Route::get('/', [HomeController::class, 'index'])->name('home');
Route::any('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');

Route::get('/exam/start/{examNo?}', [\App\Http\Controllers\Exam\ExamController::class, 'startExam'])->name('home.exam.start');
Route::get('/exam/completed/{examNo?}', [\App\Http\Controllers\Exam\ExamController::class, 'examCompleted'])->name('home.exam.completed');
Route::get('/exam/view-result-form', [\App\Http\Controllers\Exam\ExamController::class, 'viewResultForm'])->name('home.exam.view-result-form');
Route::get('/exam/view-result', [\App\Http\Controllers\Exam\ExamController::class, 'viewResult'])->name('home.exam.view-result');


Route::group(['middleware' => ['auth:admin']], function() {
    
    Route::get('/dashboard', [UserController::class, 'index'])->name('user.dashboard');
    
    //Admin
    Route::get('/admin/dashboard', [Web\Admin\AdminController::class, 'index'])->name('admin.dashboard');
    
    Route::resource('/admin/user', Web\Admin\UserController::class, ['as' => 'admin'])
    ->except(['create']);
    Route::get('/admin/search', [Web\Admin\UserController::class, 'search'])->name('admin.user.search');

    Route::resource('/admin/institution', Web\Admin\InstitutionController::class, ['as' => 'admin']);
    Route::get('/admin/institution/assign-user/{id}', [Web\Admin\InstitutionController::class, 'assignUserView'])->name('admin.institution.assign-user');
    Route::post('/admin/institution/assign-user/{id}', [Web\Admin\InstitutionController::class, 'assignUserStore'])->name('admin.institution.assign-user');
    
});



Route::get('/rough/{instId?}', function (Request $request) {
//     http://mock.examscholars.com/exam-img.php?course_id=32&course_session_id=206
// &filename=../../../../../exam-img.php?course_id=16&course_session_id=475
// &filename=image_60b7f1b7d30b6-319.png&session=2011&session=2011
    $filename = //"image_60b7f1b7d30b6-319.png";
    "../../../../../exam-img.php?course_id=16&course_session_id=475&filename=image_60b7f1b7d30b6-319.png&session=2011&session=2011";
    
    function parseFilename($filename)
    {
        $urlparts = parse_url($filename);//['path'];//getUrlPath();
        
        if(empty($urlparts['path'])) return $filename;
//         dDie($urlparts);
        if(empty($urlparts['query'])) return $urlparts['path'];
        
        parse_str($urlparts['query'], $urlparts2);
        
        return parseFilename($urlparts2['filename']);
    }
    
    $parsedFilename = parseFilename($filename);//['path'];//getUrlPath();
    
    dd($parsedFilename);
    
    // dlog_22("Filename 1 = $filename");
    if(stripos($filename, '?')){
        $filename = substr($filename, 0, stripos($filename, '?'));
    }
    
    // dlog_22("Filename 2 = $filename");
    if($slashPositon = strripos($filename, '/')){
        $filename = substr($filename, $slashPositon+1);
    }
    
    
   die($filename);
});
