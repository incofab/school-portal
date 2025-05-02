<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;
use App\Mail\AdmissionLetterMail;
use App\Mail\InstitutionMessageMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

//Institution

Route::get('dummy', function () {
  // dd('dmoddsdsd');
  // Mail::to('email@email.com')->send(new AdmissionLetterMail(User::first()));
  // Mail::to('email@email.com')->queue(new AdmissionLetterMail(User::first()));
  // return new InstitutionMessageMail('Welcome', 'This is a welcome message');
  $url = "https://texturl.com";
  // return new AdmissionLetterMail(User::first(), $url);
})->name('dummy');

Route::get('/dashboard', [Web\InstitutionController::class, 'index'])
  ->name('dashboard');  
Route::get('/dashboard/setup-checklist', [Web\InstitutionController::class, 'setupChecklist'])
  ->name('dashboard.setup-checklist');
Route::get('/profile', [Web\InstitutionController::class, 'profile'])
  ->name('profile');
Route::put('/update', [Web\InstitutionController::class, 'update'])
  ->name('update');
Route::post('/upload-photo', [Web\InstitutionController::class, 'uploadPhoto'])
  ->name('upload-photo');

Route::get('/classifications/search', [Web\Classifications\ClassificationController::class, 'search'])
  ->name('classifications.search');
Route::post('/classifications/{classification}/migrate-students', [Web\Classifications\UpdateStudentClassController::class, 'migrateClassStudents'])
  ->name('classifications.migrate-students');

Route::get('/student-class-movements', [Web\Classifications\StudentClassMovementController::class, 'index'])
  ->name('student-class-movements.index');
Route::get('/student-class-movements/search', [Web\Classifications\StudentClassMovementController::class, 'search'])
  ->name('student-class-movements.search');
Route::post('/student-class-movements/batch-revert', [Web\Classifications\RevertStudentClassMovementController::class, 'revertBatchStudentClassMovement'])
  ->name('student-class-movements.batch-revert');
Route::post('/student-class-movements/{studentClassMovement}/revert', [Web\Classifications\RevertStudentClassMovementController::class, 'revertSingleStudentClassMovement'])
  ->name('student-class-movements.revert');

Route::get('/classifications/download', [Web\Classifications\ClassificationController::class, 'download'])
  ->name('classifications.download');
Route::post('/classifications/upload', [Web\Classifications\ClassificationController::class, 'upload'])
  ->name('classifications.upload');
Route::resource('/classifications', Web\Classifications\ClassificationController::class)
  ->except(['show']);

Route::get('/classification-groups/search', [Web\Classifications\ClassificationGroupController::class, 'search'])
  ->name('classification-groups.search');
Route::resource('/classification-groups', Web\Classifications\ClassificationGroupController::class)
  ->except(['show']);

Route::get('/classification-groups/{classificationGroup}/promote-students/{destinationClassificatiinGroup?}', [Web\Classifications\PromoteStudentsController::class, 'create'])
  ->name('classification-groups.promote-students.create');
Route::post('/classification-groups/{classificationGroup}/promote-students', [Web\Classifications\PromoteStudentsController::class, 'store'])
  ->name('classification-groups.promote-students.store');

// Route::get('/students/search', Web\Students\SearchStudentController::class)
//     ->name('students.search');
// Route::get('/students/term-result-detail/{student}/{classification}/{academicSession}/{term}/{forMidTerm}', Web\Students\StudentTermResultDetailController::class)
//     ->name('students.term-result-detail');
// Route::get('/students/result-sheet/{student}/{classification}/{academicSession}/{term}/{forMidTerm}', [Web\Students\ViewResultSheetController::class, 'viewResult'])
//     ->name('students.result-sheet');
// Route::get('/students/{student}/transcript', Web\Students\ShowTranscriptController::class)->name('students.transcript');
// Route::get('/students/term-results', Web\Students\ListStudentTermResultController::class)
//     ->name('students.term-results.index');
// Route::get('/session-results/index', [Web\Students\SessionResultController::class, 'index'])
//     ->name('session-results.index');
// Route::get('/session-results/{sessionResult}', [Web\Students\SessionResultController::class, 'show'])
//     ->name('session-results.show');
// Route::delete('/session-results/{sessionResult}', [Web\Students\SessionResultController::class, 'destroy'])
//     ->name('session-results.destroy');

// Route::get('/users/{user}/receipts', [Web\Students\StudentFeePaymentController::class, 'receipts'])->name('users.receipts.index');
// Route::get('/users/{user}/fee-payments/{receipt}', [Web\Students\StudentFeePaymentController::class, 'index'])->name('users.fee-payments.index');
// Route::get('/receipts/{receipt:reference}/show', [Web\Students\StudentFeePaymentController::class, 'showReceipt'])->name('receipts.show');
// Route::get('/students/{student}/fee-payments/create', [Web\Students\StudentFeePaymentController::class, 'feePaymentView'])->name('students.fee-payments.create');
// Route::post('/students/{student}/fee-payments/store', [Web\Students\StudentFeePaymentController::class, 'feePaymentStore'])->name('students.fee-payments.store');


Route::post('/students/{student}/update-code', [Web\Staff\StudentManagementController::class, 'updateCode'])
  ->name('students.update-code');
Route::get('/classifications/{classification}/students-download', Web\Classifications\DownloadClassStudentsController::class)
  ->name('classifications.students-download');
Route::get('/students/download-recording-template', [Web\Staff\StudentManagementController::class, 'downloadTemplate'])
  ->name('students.download-recording-template');
Route::post('/students/upload/{classification}', [Web\Staff\StudentManagementController::class, 'uploadStudents'])
  ->name('students.upload');

Route::resource('/students', Web\Staff\StudentManagementController::class)->except(['show']);
Route::post('/term-results/{termResult}/teacher-comment', [Web\Staff\TermResultCommentController::class, 'teacherComment'])
  ->name('term-results.teacher-comment');
Route::post('/term-results/{termResult}/principal-comment', [Web\Staff\TermResultCommentController::class, 'principalComment'])
  ->name('term-results.principal-comment');
Route::post('/term-results/{termResult}/extra-data-update', Web\Staff\UpdateTermResultExtraDataController::class)
  ->name('term-results.extra-data.update');
Route::get('/term-details/{termDetail?}', [Web\Staff\TermDetailController::class, 'index'])
  ->name('term-details.index');
Route::put('/term-details/{termDetail}/update', [Web\Staff\TermDetailController::class, 'update'])
  ->name('term-details.update');
Route::post('/students/{student}/change-class', [Web\Classifications\UpdateStudentClassController::class, 'changeStudentClass'])
  ->name('students.change-class');
Route::get('/change-multi-student-class/{classification}', [Web\Classifications\UpdateStudentClassController::class, 'changeMultipleStudentClassView'])
  ->name('change-multi-student-class.create');
Route::post('/change-multi-student-class', [Web\Classifications\UpdateStudentClassController::class, 'changeMultipleStudentClass'])
  ->name('change-multi-student-class.store');
Route::get('/classifications/{classification}/students', [Web\Staff\StudentManagementController::class, 'classStudentsTiles'])
  ->name('classifications.students');
Route::get('/classifications/{classification}/idcards', [Web\Staff\StudentManagementController::class, 'classStudentsIdCards'])
  ->name('classifications.idcards');

Route::get('/guardians', [Web\Staff\GuardianManagementController::class, 'index'])
  ->name('guardians.index');
Route::get('/guardians/classifications/{classification}/create', [Web\Staff\GuardianManagementController::class, 'create'])
  ->name('guardians.classifications.create');
Route::post('/guardians/classifications/{classification}/store', [Web\Staff\GuardianManagementController::class, 'store'])
    ->name('guardians.classifications.store');
Route::post('/guardians/{guardianUser}/assign-student', [Web\Staff\GuardianManagementController::class, 'assignStudent'])
    ->name('guardians.assign-student');
Route::get('/guardians/list-dependents', Web\Guardians\ListDependentsController::class)
  ->name('guardians.list-dependents');
Route::delete('/guardians/remove-dependent/{student}', Web\Guardians\RemoveDependentController::class)
  ->name('guardians.remove-dependent');

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
Route::get('/users/idcards/{classification?}', [Web\Users\InstitutionUserController::class, 'idCards'])
  ->name('users.idcards');
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

Route::get('/record-class-results/{courseTeacher}', [Web\Staff\RecordClassResultController::class, 'create'])
  ->name('record-class-results.create');
Route::post('/record-class-results/{courseTeacher}', [Web\Staff\RecordClassResultController::class, 'store'])
  ->name('record-class-results.store');

Route::get('/course-result-info/index', Web\Staff\ListCourseResultInfoController::class)
  ->name('course-result-info.index');

Route::get('/class-result-info/index', [Web\Staff\ClassResultInfoController::class, 'index'])
  ->name('class-result-info.index');
Route::post('/class-result-info/calculate/{classification}', [Web\Staff\ClassResultInfoController::class, 'calculate'])
  ->name('class-result-info.calculate');
Route::post('/class-result-info/recalculate/{classResultInfo}', [Web\Staff\ClassResultInfoController::class, 'reCalculate'])
  ->name('class-result-info.recalculate');
Route::post('/class-result-info/set-resumption-date/{classificationGroup?}', [Web\Staff\ClassResultInfoController::class, 'setNextTermResumptionDate'])
  ->name('class-result-info.set-resumption-date');

Route::get('/term-results/index/{user?}', Web\ListTermResultController::class)
  ->name('term-results.index');
Route::get('/cummulative-result/index', Web\Staff\CummulativeResultController::class)
  ->name('cummulative-result.index');

Route::get('/pins/classifications/{classification}/tiles', [Web\Staff\Pins\StudentPinController::class, 'indexTiles'])
  ->name('pins.classification.student-pin-tiles');
Route::post('/pins/students/{student}', [Web\Staff\Pins\StudentPinController::class, 'storeStudentPin'])
  ->name('pins.students.store');
Route::post('/pins/classifications/{classification}', [Web\Staff\Pins\StudentPinController::class, 'storeClassStudentPin'])
  ->name('pins.classifications.store');

Route::get('/pin-generators/{pinGenerator}/download', [Web\Pins\PinGeneratorController::class, 'downloadPins'])
  ->name('pin-generators.download');
Route::get('/pin-generators', [Web\Pins\PinGeneratorController::class, 'index'])
  ->name('pin-generators.index');
Route::get('/pin-generators/create', [Web\Pins\PinGeneratorController::class, 'create'])
  ->name('pin-generators.create');
Route::get('/pin-generators/{pinGenerator}', [Web\Pins\PinGeneratorController::class, 'show'])
  ->name('pin-generators.show');
Route::post('/pin-generators/store', [Web\Pins\PinGeneratorController::class, 'store'])
  ->name('pin-generators.store');

Route::get('/fees/search', [Web\Payments\FeeController::class, 'search'])->name('fees.search');
Route::resource('/fees', Web\Payments\FeeController::class)->except(['show']);

// Route::get('/fee-payments/download/{classification}/{receiptType}', [Web\Payments\FeePaymentController::class, 'download'])->name('fee-payments.download');
// Route::post('/fee-payments/upload', [Web\Payments\FeePaymentController::class, 'upload'])->name('fee-payments.upload');
Route::get('/fee-payments/index/{fee?}', [Web\Payments\FeePaymentController::class, 'index'])->name('fee-payments.index');
Route::resource('/fee-payments', Web\Payments\FeePaymentController::class)->except(['index', 'edit', 'update']);
Route::get('/receipts', [Web\Payments\ReceiptController::class, 'index'])->name('receipts.index');
Route::get('/receipts/{receipt}', [Web\Payments\ReceiptController::class, 'show'])->name('receipts.show');

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

Route::get('/result-comment-templates/index/{resultCommentTemplate?}', [Web\Staff\ResultCommentTemplateController::class, 'index'])
  ->name('result-comment-templates.index');
Route::post('/result-comment-templates/store/{resultCommentTemplate?}', [Web\Staff\ResultCommentTemplateController::class, 'store'])
  ->name('result-comment-templates.store');
Route::delete('/result-comment-templates/destroy/{resultCommentTemplate}', [Web\Staff\ResultCommentTemplateController::class, 'destroy'])
  ->name('result-comment-templates.destroy');

Route::get('/learning-evaluation-domains/index/{learningEvaluationDomain?}', [Web\Staff\LearningEvaluationDomainController::class, 'index'])
  ->name('learning-evaluation-domains.index');
Route::post('/learning-evaluation-domains/store/{learningEvaluationDomain?}', [Web\Staff\LearningEvaluationDomainController::class, 'store'])
  ->name('learning-evaluation-domains.store');
Route::delete('/learning-evaluation-domains/destroy/{learningEvaluationDomain}', [Web\Staff\LearningEvaluationDomainController::class, 'destroy'])
  ->name('learning-evaluation-domains.destroy');

Route::get('/learning-evaluations/index/{learningEvaluation?}', [Web\Staff\LearningEvaluationController::class, 'index'])
  ->name('learning-evaluations.index');
Route::post('/learning-evaluations/store/{learningEvaluation?}', [Web\Staff\LearningEvaluationController::class, 'store'])
  ->name('learning-evaluations.store');
Route::delete('/learning-evaluations/destroy/{learningEvaluation}', [Web\Staff\LearningEvaluationController::class, 'destroy'])
  ->name('learning-evaluations.destroy');
Route::post('/set-term-result-learning-evaluation/{termResult?}', [Web\Staff\LearningEvaluationController::class, 'setTermResultEvaluation'])
  ->name('set-term-result-learning-evaluation');

Route::resource('/admission-applications', Web\Admissions\AdmissionApplicationController::class)->except('store');
Route::post('/admission-applications/{admissionApplication}/update-status', [Web\Admissions\AdmissionApplicationController::class, 'updateStatus'])
  ->name('admission-applications.update-status');

Route::resource('admission-forms', Web\Admissions\AdmissionFormController::class);

Route::resource('associations', Web\Associations\AssociationController::class)->except('edit', 'create');

Route::get('user-associations/index/{association}', [Web\Associations\UserAssociationController::class, 'index'])->name('user-associations.index');
Route::get('user-associations/create/{morphClass?}/{morphId?}', [Web\Associations\UserAssociationController::class, 'create'])->name('user-associations.create');
Route::post('user-associations/store', [Web\Associations\UserAssociationController::class, 'store'])->name('user-associations.store');
Route::delete('user-associations/{userAssociation}/destroy', [Web\Associations\UserAssociationController::class, 'destroy'])->name('user-associations.destroy');


include base_path('routes/assignment.php');
include base_path('routes/attendance.php');
include base_path('routes/exam.php');
include base_path('routes/student_routes.php');

Route::get('/transactions/{walletType?}', [Web\Fundings\TransactionController::class, 'index'])->name('transactions.index');
Route::resource('/fundings', Web\Fundings\FundingsController::class)->only('create', 'store');
Route::get('/fundings/{walletType?}', [Web\Fundings\FundingsController::class, 'index'])->name('fundings.index');
Route::resource('/result-publications', Web\ResultPublications\ResultPublicationsController::class)->only('index', 'create', 'store');

//A route named 'topics' already exist in 'ccd' route file, hence I use 'inst-topics'
Route::get('/inst-topics', [Web\Curriculums\TopicController::class, 'index'])->name('inst-topics.index');
// Route::get('/inst-topics/{topic}/edit', [Web\Curriculums\TopicController::class, 'edit'])->name('inst-topics.edit');
Route::get('/inst-topics/{topic}/sub-topics', [Web\Curriculums\TopicController::class, 'subTopicIndex'])->name('inst-topics.sub-topics');
Route::delete('/inst-topics/{topic}/destroy', [Web\Curriculums\TopicController::class, 'destroy'])->name('inst-topics.destroy');
// Route::post('/inst-topics/{parentTopicId?}', [Web\Curriculums\TopicController::class, 'updateOrCreate'])->name('inst-topics.store');
// Route::put('/inst-topics/{topic?}', [Web\Curriculums\TopicController::class, 'updateOrCreate'])->name('inst-topics.update');
Route::post('/inst-topics/{topic?}', [Web\Curriculums\TopicController::class, 'storeOrUpdate'])->name('inst-topics.store-or-update');
Route::get('/inst-topics/create-edit/{topic?}', [Web\Curriculums\TopicController::class, 'createOrEdit'])->name('inst-topics.create-or-edit');
Route::get('/inst-topics/search', [Web\Curriculums\TopicController::class, 'search'])->name('inst-topics.search');
Route::get('/inst-topics/{topic}', [Web\Curriculums\TopicController::class, 'show'])->name('inst-topics.show');

//== SCHEME OF WORK ::
Route::get('/scheme-of-works/{topic}/create', [Web\Curriculums\SchemeOfWorkController::class, 'create'])->name('scheme-of-works.create');
Route::resource('/scheme-of-works', Web\Curriculums\SchemeOfWorkController::class)->except('create');

//== LESSON PLAN ::
// Route::resource('/lesson-plans', Web\Curriculum\LessonPlanController::class);
Route::get('/lesson-plans', [Web\Curriculums\LessonPlanController::class, 'index'])->name('lesson-plans.index');
Route::get('/lesson-plans/{schemeOfWork}/create', [Web\Curriculums\LessonPlanController::class, 'createOrEdit'])->name('lesson-plans.create');
Route::get('/lesson-plans/{lessonPlan}/edit', [Web\Curriculums\LessonPlanController::class, 'createOrEdit'])->name('lesson-plans.edit');
Route::get('/lesson-plans/{lessonPlan}', [Web\Curriculums\LessonPlanController::class, 'show'])->name('lesson-plans.show');
Route::post('/lesson-plans/{lessonPlan?}', [Web\Curriculums\LessonPlanController::class, 'storeOrUpdate'])->name('lesson-plans.store-or-update');
Route::delete('/lesson-plans/{lessonPlan}/destroy', [Web\Curriculums\LessonPlanController::class, 'destroy'])->name('lesson-plans.destroy');

//== LESSON NOTES ::
Route::get('/lesson-notes', [Web\Curriculums\LessonNoteController::class, 'index'])->name('lesson-notes.index');
Route::post('/lesson-notes/generate-ai-note', [Web\Curriculums\LessonNoteController::class, 'generateAiNote'])->name('lesson-notes.gen-ai-note');
Route::get('/lesson-notes/{lessonPlan}/create', [Web\Curriculums\LessonNoteController::class, 'createOrEdit'])->name('lesson-notes.create');
Route::get('/lesson-notes/{lessonNote}/edit', [Web\Curriculums\LessonNoteController::class, 'createOrEdit'])->name('lesson-notes.edit');
Route::get('/lesson-notes/{lessonNote}', [Web\Curriculums\LessonNoteController::class, 'show'])->name('lesson-notes.show');
Route::post('/lesson-notes/{lessonNote?}', [Web\Curriculums\LessonNoteController::class, 'storeOrUpdate'])->name('lesson-notes.store-or-update');
Route::delete('/lesson-notes/{lessonNote}/destroy', [Web\Curriculums\LessonNoteController::class, 'destroy'])->name('lesson-notes.destroy');

Route::get('/school-activities/search', [Web\SchoolActivities\SchoolActivityController::class, 'search'])
  ->name('school-activities.search');
Route::resource('/school-activities', Web\SchoolActivities\SchoolActivityController::class);

Route::get('/timetables/{classification}/class', [Web\Timetables\TimetableController::class, 'classTimetable'])
  ->name('timetables.classTimetable');
Route::resource('/timetables', Web\Timetables\TimetableController::class);


Route::post('/payment-notifications', [Web\PaymentNotifications\PaymentNotificationController::class, 'store'])->name('payment-notifications.store');

Route::get('/messages/index', [Web\Staff\MessageController::class, 'index'])->name('messages.index');
Route::post('/messages/store', [Web\Staff\MessageController::class, 'store'])->name('messages.store');
Route::get('/messages/create', [Web\Staff\MessageController::class, 'create'])->name('messages.create');
