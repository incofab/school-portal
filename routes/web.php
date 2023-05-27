<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Institution\EventController;
use App\Http\Controllers\Institution\StudentController;
use App\Http\Controllers\CCD\CourseController;
use App\Http\Controllers\CCD\SessionController;
use App\Http\Controllers\CCD\QuestionController;
use App\Http\Controllers\Institution\InstitutionController;
use Illuminate\Http\Request;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Institution\GradeController;
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
  
    // Route::get('students/register', Web\Students\CreateStudentController::class)
    // ->name('students.register.create');
    // Route::post('register', Web\Students\StoreStudentController::class)
    // ->name('students.register.store');
    // Route::get('students/search', Web\Students\SearchStudentController::class)
    // ->name('students.search');
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

Route::group(['middleware' => ['auth', 'institution.user']], function() {
    
    //Institution
    Route::any('/institution/dashboard/{institution_id}', [InstitutionController::class, 'index'])->name('institution.dashboard');
    
    // Institution Event
    Route::resource('/institution/{institution_id}/event', EventController::class, ['as' => 'institution'])
    ->except(['destroy']);
//     Route::any('/institution/{institution_id}/event/{id}/update', [EventController::class, 'update'])->name('institution.event.update');
    Route::any('/institution/{institution_id}/event/suspend', [EventController::class, 'suspend'])->name('institution.event.suspend');
    Route::any('/institution/{institution_id}/event/unsuspend', [EventController::class, 'unsuspend'])->name('institution.event.unsuspend');
    Route::any('/institution/{institution_id}/event/result/{id}', [EventController::class, 'eventResult'])->name('institution.event.result');
    Route::any('/institution/{institution_id}/event/destroy/{id}', [EventController::class, 'destroy'])->name('institution.event.destroy');
    Route::any('/institution/{institution_id}/event/result-download/{id}', [EventController::class, 'downloadEventResult'])->name('institution.event.result-download');
    
    // Institution Student
    Route::resource('/institution/{institution_id}/student', StudentController::class, ['as' => 'institution'])
    ->except(['index', 'destroy']);
    Route::get('/institution/{institution_id}/student/delete/{id}', [StudentController::class, 'destroy'])->name('institution.student.destroy');
    Route::get('/institution/{institution_id}/students/{gradeId?}', [StudentController::class, 'index'])->name('institution.student.index');
    Route::get('/institution/{institution_id}/student/upload/create', [StudentController::class, 'uploadStudentsView'])->name('institution.student.upload.create');
    Route::post('/institution/{institution_id}/student/upload/store', [StudentController::class, 'uploadStudents'])->name('institution.student.upload.store');
    Route::post('/institution/{institution_id}/student/manage/suspend', [StudentController::class, 'suspend'])->name('institution.student.suspend');
    Route::post('/institution/{institution_id}/student/manage/unsuspend', [StudentController::class, 'unsuspend'])->name('institution.student.unsuspend');
    Route::get('/institution/{institution_id}/student/multi/create', [StudentController::class, 'multiStudentCreate'])->name('institution.student.multi-create');
    Route::post('/institution/{institution_id}/student/multi/create', [StudentController::class, 'multiStudentStore'])->name('institution.student.multi-store');
    Route::get('/institution/{institution_id}/student/manage/download-sample-file', [StudentController::class, 'downloadSampleExcel'])->name('institution.student.download-sample-excel');
    Route::post('/institution/{institution_id}/student/multi-delete', [StudentController::class, 'multiDelete'])->name('institution.student.multi-delete');

    Route::resource('/institution/{institution_id}/grade', GradeController::class, ['as' => 'institution']);
    
    // Institution Exam
    Route::resource('/institution/{institution_id}/exam', \App\Http\Controllers\Institution\ExamController::class, ['as' => 'institution'])
    ->except(['create', 'index', 'show']);
    Route::get('/institution/{institution_id}/exam/manage/index/{eventId?}', [\App\Http\Controllers\Institution\ExamController::class, 'index'])->name('institution.exam.index');
    Route::get('/institution/{institution_id}/exam/manage/create/{studentId?}', [\App\Http\Controllers\Institution\ExamController::class, 'create'])->name('institution.exam.create');
    Route::get('/institution/{institution_id}/exam/manage/extend/{examNo}', [\App\Http\Controllers\Institution\ExamController::class, 'extendExamTimeView'])->name('institution.exam.extend');
    Route::post('/institution/{institution_id}/exam/manage/extend/{examNo}', [\App\Http\Controllers\Institution\ExamController::class, 'extendExamTime'])->name('institution.exam.extend.store');
    Route::get('/institution/{institution_id}/exam/grade/create/{gradeId?}', [\App\Http\Controllers\Institution\ExamController::class, 'createGradeExam'])->name('institution.exam.grade.create');
    Route::post('/institution/{institution_id}/exam/grade/create/{gradeId?}', [\App\Http\Controllers\Institution\ExamController::class, 'storeGradeExam'])->name('institution.exam.grade.store');

    /**** CCD *****/ 
    // CCD Course
    Route::resource('/ccd/institution/{institution_id}/course', CourseController::class, ['as' => 'ccd'])
    ->except(['show', 'destroy']);
    Route::get('/ccd/institution/{institution_id}/course/{courseId}/delete', [CourseController::class, 'delete'])->name('ccd.course.delete');

    // CCD Session
    Route::resource('/ccd/institution/{institution_id}/session', SessionController::class, ['as' => 'ccd'])
    ->except(['index', 'create', 'store']);
    Route::any('/ccd/institution/{institution_id}/session/preview/{id}', [SessionController::class, 'preview'])->name('ccd.session.preview');
    Route::get('/ccd/institution/{institution_id}/sessions/{courseId}', [SessionController::class, 'index'])->name('ccd.session.index');
    Route::get('/ccd/institution/{institution_id}/session/create/{courseId}', [SessionController::class, 'create'])->name('ccd.session.create');
    Route::post('/ccd/institution/{institution_id}/session/store/{courseId}', [SessionController::class, 'store'])->name('ccd.session.store');
    Route::get('/ccd/institution/{institution_id}/session/store/{courseId}/upload-excel-questions/{courseSessionId}', [SessionController::class, 'uploadExcelQuestionCreate'])->name('ccd.session.upload-excel-question');
    Route::post('/ccd/institution/{institution_id}/session/store/{courseId}/upload-excel-questions/{courseSessionId}', [SessionController::class, 'uploadExcelQuestionStore']);
    
    // CCD Question
    Route::resource('/ccd/institution/{institution_id}/question', QuestionController::class, ['as' => 'ccd'])
    ->except(['index', 'create', 'store']);
    Route::get('/ccd/institution/{institution_id}/questions/{sessionId}', [QuestionController::class, 'index'])->name('ccd.question.index');
    Route::get('/ccd/institution/{institution_id}/question/create/{sessionId}', [QuestionController::class, 'create'])->name('ccd.question.create');
    Route::post('/ccd/institution/{institution_id}/question/create/{sessionId}', [QuestionController::class, 'store'])->name('ccd.question.store');
    Route::any('/ccd/image-upload/institution/{institution_id}/question/{courseId}/{sessionId}', [\App\Http\Controllers\CCD\HomeController::class, 'uploadImage'])->name('ccd.question.upload-image');

    //Content Upload
    Route::get('/ccd/institution/{institution_id}/course/upload/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'uploadCourseView'])->name('ccd.course.upload');
    Route::post('/ccd/institution/{institution_id}/course/upload/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'uploadCourse'])->name('ccd.course.upload.store');
    Route::get('/ccd/institution/{institution_id}/course/uninstall/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'unInstallCourse'])->name('ccd.course.uninstall');
    Route::get('/ccd/institution/{institution_id}/course/export/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'exportCourse'])->name('ccd.course.export');
    
    /*** // CCD ***/
    
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
    
    dDie($parsedFilename);
    
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

// Route::group(['prefix' => 'admin'], function () {
//     Voyager::routes();
// });