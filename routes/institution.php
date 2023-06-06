<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;
    
//Institution

Route::get('/dashboard', [Web\InstitutionController::class, 'index'])
    ->name('dashboard');

Route::get('/classifications/search', [Web\ClassificationController::class, 'search'])
->name('classifications.search');
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
        '/students/term-result-detail/{student}/{classification}/{academicSession}/{term}', 
        Web\Students\StudentTermResultDetailController::class
    )->name('students.term-result-detail');
Route::get(
        '/students/result-sheet/{student}/{classification}/{academicSession}/{term}', 
        Web\Students\ViewResultSheetController::class
    )->name('students.result-sheet');
Route::resource('/students', Web\Students\StudentController::class);

Route::get('/courses/search', [Web\CoursesController::class, 'search'])
    ->name('courses.search');
Route::resource('/courses', Web\CoursesController::class);

Route::get('/users/{institutionUser}/edit', [Web\Users\InstitutionUserController::class, 'edit'])
    ->name('users.edit');
Route::put('/users/{institutionUser}/edit', [Web\Users\InstitutionUserController::class, 'update'])
    ->name('users.update');
Route::resource('/users', Web\Users\InstitutionUserController::class)
    ->only(['create', 'store']);

Route::get('/users/index', [Web\Users\ListInstitutionUserController::class, 'index'])
    ->name('users.index');
Route::get('/users/search', [Web\Users\ListInstitutionUserController::class, 'search'])
    ->name('users.search');
Route::get('/users/{user}/profile', [Web\Users\UpdateInstitutionUserController::class, 'edit'])
    ->name('users.profile');
Route::put('/users/{user}/update', [Web\Users\UpdateInstitutionUserController::class, 'update'])
    ->name('users.update');
Route::get('/users/download-recording-template', [Web\Users\InstitutionUserController::class, 'downloadTemplate'])
    ->name('users.download-recording-template');
Route::post('/users/upload', [Web\Users\InstitutionUserController::class, 'uploadStaff'])
    ->name('users.upload');
Route::post('/users/{user}/upload-phone', [Web\Users\UpdateInstitutionUserController::class, 'uploadPhoto'])
    ->name('users.upload-photo');

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
Route::post('/course-results/upload/{courseTeacher}', [Web\Staff\CourseResultsController::class, 'upload'])
    ->name('course-results.upload');
Route::get('/course-results/download', Web\Staff\DownloadCourseResultSheetController::class)
    ->name('course-results.download');

Route::get('/class-result-info/index', [Web\Staff\ClassResultInfoController::class, 'index'])
    ->name('class-result-info.index');
Route::post('/class-result-info/calculate/{classification}', [Web\Staff\ClassResultInfoController::class, 'calculate'])
    ->name('class-result-info.calculate');
Route::post('/class-result-info/recalculate/{classResultInfo}', [Web\Staff\ClassResultInfoController::class, 'reCalculate'])
    ->name('class-result-info.recalculate');

Route::get('/term-results/index/{user?}', Web\ListTermResultController::class)
    ->name('term-results.index');





    

// Institution Event
// Route::resource('/institution/{institution_id}/event', EventController::class, ['as' => 'institution'])
// ->except(['destroy']);
// //     Route::any('/institution/{institution_id}/event/{id}/update', [EventController::class, 'update'])->name('institution.event.update');
// Route::any('/institution/{institution_id}/event/suspend', [EventController::class, 'suspend'])->name('institution.event.suspend');
// Route::any('/institution/{institution_id}/event/unsuspend', [EventController::class, 'unsuspend'])->name('institution.event.unsuspend');
// Route::any('/institution/{institution_id}/event/result/{id}', [EventController::class, 'eventResult'])->name('institution.event.result');
// Route::any('/institution/{institution_id}/event/destroy/{id}', [EventController::class, 'destroy'])->name('institution.event.destroy');
// Route::any('/institution/{institution_id}/event/result-download/{id}', [EventController::class, 'downloadEventResult'])->name('institution.event.result-download');

// // Institution Student
// Route::resource('/institution/{institution_id}/student', StudentController::class, ['as' => 'institution'])
// ->except(['index', 'destroy']);
// Route::get('/institution/{institution_id}/student/delete/{id}', [StudentController::class, 'destroy'])->name('institution.student.destroy');
// Route::get('/institution/{institution_id}/students/{gradeId?}', [StudentController::class, 'index'])->name('institution.student.index');
// Route::get('/institution/{institution_id}/student/upload/create', [StudentController::class, 'uploadStudentsView'])->name('institution.student.upload.create');
// Route::post('/institution/{institution_id}/student/upload/store', [StudentController::class, 'uploadStudents'])->name('institution.student.upload.store');
// Route::post('/institution/{institution_id}/student/manage/suspend', [StudentController::class, 'suspend'])->name('institution.student.suspend');
// Route::post('/institution/{institution_id}/student/manage/unsuspend', [StudentController::class, 'unsuspend'])->name('institution.student.unsuspend');
// Route::get('/institution/{institution_id}/student/multi/create', [StudentController::class, 'multiStudentCreate'])->name('institution.student.multi-create');
// Route::post('/institution/{institution_id}/student/multi/create', [StudentController::class, 'multiStudentStore'])->name('institution.student.multi-store');
// Route::get('/institution/{institution_id}/student/manage/download-sample-file', [StudentController::class, 'downloadSampleExcel'])->name('institution.student.download-sample-excel');
// Route::post('/institution/{institution_id}/student/multi-delete', [StudentController::class, 'multiDelete'])->name('institution.student.multi-delete');

// Route::resource('/institution/{institution_id}/grade', GradeController::class, ['as' => 'institution']);

// // Institution Exam
// Route::resource('/institution/{institution_id}/exam', \App\Http\Controllers\Institution\ExamController::class, ['as' => 'institution'])
// ->except(['create', 'index', 'show']);
// Route::get('/institution/{institution_id}/exam/manage/index/{eventId?}', [\App\Http\Controllers\Institution\ExamController::class, 'index'])->name('institution.exam.index');
// Route::get('/institution/{institution_id}/exam/manage/create/{studentId?}', [\App\Http\Controllers\Institution\ExamController::class, 'create'])->name('institution.exam.create');
// Route::get('/institution/{institution_id}/exam/manage/extend/{examNo}', [\App\Http\Controllers\Institution\ExamController::class, 'extendExamTimeView'])->name('institution.exam.extend');
// Route::post('/institution/{institution_id}/exam/manage/extend/{examNo}', [\App\Http\Controllers\Institution\ExamController::class, 'extendExamTime'])->name('institution.exam.extend.store');
// Route::get('/institution/{institution_id}/exam/grade/create/{gradeId?}', [\App\Http\Controllers\Institution\ExamController::class, 'createGradeExam'])->name('institution.exam.grade.create');
// Route::post('/institution/{institution_id}/exam/grade/create/{gradeId?}', [\App\Http\Controllers\Institution\ExamController::class, 'storeGradeExam'])->name('institution.exam.grade.store');

// /**** CCD *****/ 
// // CCD Course
// Route::resource('/ccd/institution/{institution_id}/course', CourseController::class, ['as' => 'ccd'])
// ->except(['show', 'destroy']);
// Route::get('/ccd/institution/{institution_id}/course/{courseId}/delete', [CourseController::class, 'delete'])->name('ccd.course.delete');

// // CCD Session
// Route::resource('/ccd/institution/{institution_id}/session', SessionController::class, ['as' => 'ccd'])
// ->except(['index', 'create', 'store']);
// Route::any('/ccd/institution/{institution_id}/session/preview/{id}', [SessionController::class, 'preview'])->name('ccd.session.preview');
// Route::get('/ccd/institution/{institution_id}/sessions/{courseId}', [SessionController::class, 'index'])->name('ccd.session.index');
// Route::get('/ccd/institution/{institution_id}/session/create/{courseId}', [SessionController::class, 'create'])->name('ccd.session.create');
// Route::post('/ccd/institution/{institution_id}/session/store/{courseId}', [SessionController::class, 'store'])->name('ccd.session.store');
// Route::get('/ccd/institution/{institution_id}/session/store/{courseId}/upload-excel-questions/{courseSessionId}', [SessionController::class, 'uploadExcelQuestionCreate'])->name('ccd.session.upload-excel-question');
// Route::post('/ccd/institution/{institution_id}/session/store/{courseId}/upload-excel-questions/{courseSessionId}', [SessionController::class, 'uploadExcelQuestionStore']);

// // CCD Question
// Route::resource('/ccd/institution/{institution_id}/question', QuestionController::class, ['as' => 'ccd'])
// ->except(['index', 'create', 'store']);
// Route::get('/ccd/institution/{institution_id}/questions/{sessionId}', [QuestionController::class, 'index'])->name('ccd.question.index');
// Route::get('/ccd/institution/{institution_id}/question/create/{sessionId}', [QuestionController::class, 'create'])->name('ccd.question.create');
// Route::post('/ccd/institution/{institution_id}/question/create/{sessionId}', [QuestionController::class, 'store'])->name('ccd.question.store');
// Route::any('/ccd/image-upload/institution/{institution_id}/question/{courseId}/{sessionId}', [\App\Http\Controllers\CCD\HomeController::class, 'uploadImage'])->name('ccd.question.upload-image');

// //Content Upload
// Route::get('/ccd/institution/{institution_id}/course/upload/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'uploadCourseView'])->name('ccd.course.upload');
// Route::post('/ccd/institution/{institution_id}/course/upload/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'uploadCourse'])->name('ccd.course.upload.store');
// Route::get('/ccd/institution/{institution_id}/course/uninstall/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'unInstallCourse'])->name('ccd.course.uninstall');
// Route::get('/ccd/institution/{institution_id}/course/export/{courseId}', [\App\Http\Controllers\CCD\CourseUploadController::class, 'exportCourse'])->name('ccd.course.export');
