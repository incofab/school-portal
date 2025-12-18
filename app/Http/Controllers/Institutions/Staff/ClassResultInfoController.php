<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\ClassResultInfoAction;
use App\Actions\GenericExport;
use App\Actions\Messages\SendTermResultToGuardians;
use App\Actions\Result\GetViewResultSheetData;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\ClassResultInfo;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Models\TermResult;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\ClassResultInfoUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class ClassResultInfoController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(Institution $institution, Request $request)
  {
    $query = ClassResultInfo::query()->select('class_result_info.*');
    ClassResultInfoUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/courses/list-class-result-info', [
      'classificationGroups' => Classification::query()->get(),
      'classResultInfo' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification')
          ->latest('class_result_info.id')
      )
    ]);
  }

  public function calculate(
    Institution $institution,
    Classification $classification,
    Request $request
  ) {
    $request->validate([
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'term' => ['required', new Enum(TermType::class)],
      'for_mid_term' => ['required', 'boolean'],
      'force_calculate_term_result' => ['required', 'boolean']
    ]);

    ClassResultInfoAction::make()->calculate(
      classification: $classification,
      academicSessionId: $request->academic_session_id,
      term: $request->term,
      forMidTerm: $request->for_mid_term,
      forceCalculateTermResult: $request->force_calculate_term_result
    );
    return $this->ok();
  }

  public function reCalculate(
    Institution $institution,
    ClassResultInfo $classResultInfo
  ) {
    ClassResultInfoAction::make()->reCalculate($classResultInfo);
    return $this->ok();
  }

  function setNextTermResumptionDate(
    Institution $institution,
    Request $request,
    ?ClassificationGroup $classificationGroup = null
  ) {
    $data = $request->validate([
      'next_term_resumption_date' => ['required', 'date'],
      'term' => ['required', new Enum(TermType::class)],
      'academic_session_id' => ['required', 'integer'],
      'for_all_classes' => [
        'nullable',
        'boolean',
        Rule::requiredIf(empty($classificationGroup)),
        function ($attr, $value, $fail) use ($classificationGroup) {
          if (!$value && !$classificationGroup) {
            $fail(
              'You must supply a class group if date is not to be applied to all classes'
            );
          }
        }
      ]
    ]);

    $query = ClassResultInfo::query()
      ->select('class_result_info.*')
      ->when(
        $classificationGroup,
        fn($q) => $q
          ->join(
            'classifications',
            'classifications.id',
            'class_result_info.classification_id'
          )
          ->where(
            'classifications.classification_group_id',
            $classificationGroup->id
          )
      )
      ->where('for_mid_term', false)
      ->where('term', $data['term'])
      ->where('academic_session_id', $data['academic_session_id']);

    abort_if(
      (clone $query)->get()->isEmpty(),
      403,
      'Results have not been recorded for this class in the specified term and session'
    );

    $query->update([
      'next_term_resumption_date' => $data['next_term_resumption_date']
    ]);

    return $this->ok();
  }

  function downloadClassResult(
    Institution $institution,
    ClassResultInfo $classResultInfo
  ) {
    $classResultInfo->load('classification', 'academicSession');
    $courseResults = $classResultInfo->courseResultsQuery()->get();

    $students = $classResultInfo
      ->courseResultsQuery()
      ->with('student.user')
      ->groupBy('student_id')
      ->get()
      ->map(fn($item) => $item->student);
    $courses = $classResultInfo
      ->courseResultsQuery()
      ->with('course')
      ->groupBy('course_id')
      ->get()
      ->map(fn($item) => $item->course);

    /** @var array<int, array<int, CourseResult>> $arr */
    $bySubjectsByStudents = [];
    foreach ($courseResults as $courseResult) {
      $bySubjectsByStudents[$courseResult->course_id][
        $courseResult->student_id
      ] = $courseResult;
    }

    $items = [];
    foreach ($students as $key => $student) {
      $courseResult = null;
      $item = [];
      foreach ($courses as $key => $course) {
        $courseResult =
          $bySubjectsByStudents[$course->id][$student->id] ?? null;
        $item[$course->code] = $courseResult?->result ?? '-';
      }
      if ($item) {
        $items[] = [
          'Student Id' => $student->code,
          'Name' => $student->user->full_name,
          ...$item
        ];
      }
    }

    abort_if(empty($items), 404, 'No results found');
    return (new GenericExport(
      $items,
      sanitizeFilename(
        "{$classResultInfo->classification->title}_{$classResultInfo->academicSession->title}-results.xlsx"
      )
    ))->download();
  }

  public function viewClassResultSheets(
    Request $request,
    Institution $institution,
    ClassResultInfo $classResultInfo
  ) {
    $user = currentUser();
    abort_unless(
      currentInstitutionUser()->isAdmin() ||
        $classResultInfo->classification->form_teacher_id === $user->id,
      403,
      'You are not allowed to view this page'
    );
    $classResultInfo->load('classification', 'academicSession');

    $termResults = $classResultInfo
      ->termResultsQuery()
      ->with('student')
      ->get();

    abort_unless($termResults->count(), 404, 'Result not found');

    $arr = [];
    /** @var TermResult $termResult */
    foreach ($termResults as $key => $termResult) {
      if (!$termResult->isPublished()) {
        continue;
      }
      $viewData = GetViewResultSheetData::run(
        $institution,
        $termResult->student,
        $classResultInfo->classification,
        $classResultInfo->academicSession,
        $classResultInfo->term->value,
        $classResultInfo->for_mid_term
      );
      $viewData['signed_url'] = $termResult->signedUrl();
      $arr[] = $viewData;
    }

    $setting = SettingsHandler::makeFromRoute();
    return inertia('institutions/result-sheets/class-result-sheets', [
      'classification' => $classResultInfo->classification,
      'academicSession' => $classResultInfo->academicSession,
      'term' => $classResultInfo->term,
      'forMidTerm' => $classResultInfo->for_mid_term,
      'results' => $arr,
      'resultTemplete' => $setting->getResultTemplate()
    ]);
  }

  function recordEvaluations(
    Institution $institution,
    ClassResultInfo $classResultInfo
  ) {
    $termResults = $classResultInfo
      ->termResultsQuery(fn($q) => $q->joinStudent())
      ->with('student.user')
      ->oldest('users.last_name')
      ->get();
    return inertia('institutions/students/record-class-students-evaluations', [
      'termResults' => $termResults,
      'classification' => $classResultInfo->classification,
      'academicSession' => $classResultInfo->academicSession,
      'term' => $classResultInfo->term,
      'forMidTerm' => $classResultInfo->for_mid_term,
      'learningEvaluations' => $institution
        ->learningEvaluations()
        ->with('learningEvaluationDomain')
        ->orderBy('learning_evaluation_domain_id')
        ->get(),
      'resultCommentTemplate' => ResultCommentTemplate::getTemplate(
        $classResultInfo->classification_id,
        $classResultInfo->for_mid_term
      )
    ]);
  }

  function sendResults(
    Institution $institution,
    ClassResultInfo $classResultInfo
  ) {
    $classResultInfo->load('classification', 'academicSession');
    $termResults = $classResultInfo
      ->termResultsQuery()
      ->with('student.user', 'student.guardian', 'academicSession')
      ->get();
    $user = currentUser();
    $institutionUser = currentInstitutionUser();
    abort_unless($termResults->count(), 404, 'Result not found');
    abort_unless(
      $institutionUser->isAdmin(),
      403,
      'You are not allowed to send results'
    );
    $res = (new SendTermResultToGuardians($institution, $user))->multiSend(
      $termResults
    );
    if ($res->isSuccessful()) {
      $classResultInfo->increment('whatsapp_message_count');
    }
    return $this->apiRes($res);
  }
}
