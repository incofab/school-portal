<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;
use App\Mail\InstitutionMessageMail;

//Institution

Route::get('dummy', function() {
    dd('dmoddsdsd');
    return new InstitutionMessageMail('Welcome', 'This is a welcome message');
})->name('dummy');

Route::get('/dashboard', [Web\InstitutionController::class, 'index'])
    ->name('dashboard');
Route::get('/profile', [Web\InstitutionController::class, 'profile'])
    ->name('profile');
Route::put('/update', [Web\InstitutionController::class, 'update'])
    ->name('update');
Route::post('/upload-photo', [Web\InstitutionController::class, 'uploadPhoto'])
    ->name('upload-photo');

Route::get('/classifications/search', [Web\ClassificationController::class, 'search'])
->name('classifications.search');
Route::post('/classifications/{classification}/migrate-students', [Web\Students\UpdateStudentClassController::class, 'migrateClassStudents'])
->name('classifications.migrate-students');
Route::get('/classifications/download', [Web\ClassificationController::class, 'download'])
    ->name('classifications.download');
Route::post('/classifications/upload', [Web\ClassificationController::class, 'upload'])
    ->name('classifications.upload');
Route::resource('/classifications', Web\ClassificationController::class)
    ->except(['show']);
    
Route::get('/students/search', Web\Students\SearchStudentController::class)
    ->name('students.search');
Route::get('/students/download/{classification}', Web\Students\DownloadClassStudentsController::class)
    ->name('students.download');
Route::get('/students/download-recording-template', [Web\Students\StudentController::class, 'downloadTemplate'])
    ->name('students.download-recording-template');
Route::post('/students/upload/{classification}', [Web\Students\StudentController::class, 'uploadStudents'])
    ->name('students.upload');
Route::get(
        '/students/term-result-detail/{student}/{classification}/{academicSession}/{term}/{forMidTerm}', 
        Web\Students\StudentTermResultDetailController::class
    )->name('students.term-result-detail');
Route::get(
        '/students/result-sheet/{student}/{classification}/{academicSession}/{term}/{forMidTerm}', 
        Web\Students\ViewResultSheetController::class
    )->name('students.result-sheet');
Route::resource('/students', Web\Students\StudentController::class)->except(['show']);
Route::get('/students/term-results', Web\Students\ListStudentTermResultController::class)
    ->name('students.term-results.index');
Route::post('/term-results/{termResult}/teacher-comment', [Web\Staff\TermResultCommentController::class, 'teacherComment'])
    ->name('term-results.teacher-comment');
Route::post('/term-results/{termResult}/principal-comment', [Web\Staff\TermResultCommentController::class, 'principalComment'])
    ->name('term-results.principal-comment');
Route::post('/students/{student}/change-class', [Web\Students\UpdateStudentClassController::class, 'changeStudentClass'])
    ->name('students.change-class');
Route::get('/classifications/{classification}/students', [Web\Students\StudentController::class, 'classStudentsTiles'])
    ->name('classifications.students');

Route::get('/courses/search', [Web\CoursesController::class, 'search'])
    ->name('courses.search');
Route::resource('/courses', Web\CoursesController::class);

Route::get('/users/{user}/profile', [Web\Users\UpdateInstitutionUserController::class, 'profile'])
    ->name('users.profile');
Route::get('/users/{editInstitutionUser}/edit', [Web\Users\UpdateInstitutionUserController::class, 'edit'])
    ->name('users.edit');
Route::put('/users/{editInstitutionUser}/update', [Web\Users\UpdateInstitutionUserController::class, 'update'])
    ->name('users.update');
Route::post('/users/{user}/upload-photo', [Web\Users\UpdateInstitutionUserController::class, 'uploadPhoto'])
    ->name('users.upload-photo');
Route::resource('/users', Web\Users\InstitutionUserController::class)
    ->only(['create', 'store']);

Route::get('/users/index', [Web\Users\ListInstitutionUserController::class, 'index'])
    ->name('users.index');
Route::get('/users/search', [Web\Users\ListInstitutionUserController::class, 'search'])
    ->name('users.search');
Route::get('/users/download-recording-template', [Web\Users\InstitutionUserController::class, 'downloadTemplate'])
    ->name('users.download-recording-template');
Route::post('/users/upload', [Web\Users\InstitutionUserController::class, 'uploadStaff'])
    ->name('users.upload');
Route::post('/users/{user}/reset-password', Web\Users\ResetUserPasswordController::class)
    ->name('users.reset-password');
Route::delete('/users/{user}', Web\Users\DeleteUserController::class)
    ->name('users.destroy');
Route::post('/users/{suppliedInstitutionUser}/change-role', Web\Users\ChangeUserRoleController::class)
    ->name('users.change-role');

// Teacher courses
Route::get('/course-teachers/index/{user?}', [Web\Staff\CourseTeachersController::class, 'index'])
    ->name('course-teachers.index');
Route::get('/course-teachers/search', [Web\Staff\CourseTeachersController::class, 'search'])
    ->name('course-teachers.search');
Route::get('/course-teachers/create/{user?}', [Web\Staff\CourseTeachersController::class, 'create'])
    ->name('course-teachers.create');
Route::post('/course-teachers/store/{user}', [Web\Staff\CourseTeachersController::class, 'store'])
    ->name('course-teachers.store');
Route::delete('/course-teachers/{courseTeacher}/destroy', [Web\Staff\CourseTeachersController::class, 'destroy'])
    ->name('course-teachers.destroy');

Route::get('/course-results/index', [Web\Staff\CourseResultsController::class, 'index'])
    ->name('course-results.index');
Route::get('/course-results/create/{courseTeacher}', [Web\Staff\CourseResultsController::class, 'create'])
    ->name('course-results.create');
Route::get('/course-results/{courseResult}/edit', [Web\Staff\CourseResultsController::class, 'edit'])
    ->name('course-results.edit');
Route::post('/course-results/store/{courseTeacher}', [Web\Staff\CourseResultsController::class, 'store'])
    ->name('course-results.store');
Route::delete('/course-results/{courseResult}/destroy', [Web\Staff\CourseResultsController::class, 'destroy'])
    ->name('course-results.destroy');
Route::post('/course-results/upload/{courseTeacher}', [Web\Staff\CourseResultsController::class, 'upload'])
    ->name('course-results.upload');
Route::get('/course-results/download', Web\Staff\DownloadCourseResultSheetController::class)
    ->name('course-results.download');
Route::get('/download-result-recording-sheet', Web\Staff\DownloadResultRecordingSheetController::class)
    ->name('download-result-recording-sheet');

Route::get('/course-result-info/index', Web\Staff\ListCourseResultInfo::class)
    ->name('course-result-info.index');

Route::get('/class-result-info/index', [Web\Staff\ClassResultInfoController::class, 'index'])
    ->name('class-result-info.index');
Route::post('/class-result-info/calculate/{classification}', [Web\Staff\ClassResultInfoController::class, 'calculate'])
    ->name('class-result-info.calculate');
Route::post('/class-result-info/recalculate/{classResultInfo}', [Web\Staff\ClassResultInfoController::class, 'reCalculate'])
    ->name('class-result-info.recalculate');

Route::get('/term-results/index/{user?}', Web\ListTermResultController::class)
    ->name('term-results.index');
Route::get('/cummulative-result/index', Web\Staff\CummulativeResultController::class)
    ->name('cummulative-result.index');

Route::get('/pin-prints/{pinPrint}/download', [Web\Staff\PinPrintController::class, 'downloadPins'])
    ->name('pin-prints.download');
Route::resource('/pin-prints', Web\Staff\PinPrintController::class)->only(['index', 'store', 'show']);

Route::get('/fees/search', [Web\Payments\FeeController::class, 'search'])->name('fees.search');
Route::resource('/fees', Web\Payments\FeeController::class)->except(['show']);
Route::resource('/fee-payments', Web\Payments\FeePaymentController::class)->except(['edit', 'update']);
Route::get('/settings/search', [Web\InstitutionSettingController::class, 'search'])->name('settings.search');
Route::resource('/settings', Web\InstitutionSettingController::class)->only(['index', 'create', 'store']);

Route::get('/assessments/index/{assessment?}', [Web\Staff\AssessmentController::class, 'index'])->name('assessments.index');
Route::get('/assessments/search', [Web\Staff\AssessmentController::class, 'search'])->name('assessments.search');
Route::post('/assessments/store', [Web\Staff\AssessmentController::class, 'store'])->name('assessments.store');
Route::put('/assessments/{assessment}/update', [Web\Staff\AssessmentController::class, 'update'])->name('assessments.update');
Route::delete('/assessments/{assessment}/destroy', [Web\Staff\AssessmentController::class, 'destroy'])->name('assessments.destroy');
Route::get('/assessments/{assessment}/insert-score-from-course-result', [Web\Staff\InjectAssessmentScoreFromTermResultController::class, 'create'])
    ->name('assessments.insert-score-from-course-result.create');
Route::post('/assessments/{assessment}/set-dependency', [Web\Staff\AssessmentController::class, 'setDependency'])
    ->name('assessments.set-dependency');
Route::post('/assessments/{assessment}/insert-score-from-course-result', [Web\Staff\InjectAssessmentScoreFromTermResultController::class, 'store'])
    ->name('assessments.insert-score-from-course-result.store');


