<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordStudentSubjectsResultRequest;
use App\Models\Assessment;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use App\Support\SettingsHandler;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecordStudentSubjectsResultController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function create(Institution $institution, Request $request)
  {
    $setting = SettingsHandler::makeFromRoute(true);
    $selection = $this->selection($request, $setting);
    $student = $this->selectedStudent($request);
    $courseTeachers = collect();

    if ($student && $selection['for_mid_term'] !== null) {
      $courseTeachers = $this->studentCourseTeachers($student)
        ->with(['course', 'user', 'classification'])
        ->get();
      $this->attachExistingResults($courseTeachers, $student, $selection);
    }

    return Inertia::render(
      'institutions/courses/record-student-subject-results',
      [
        'selectedStudent' => $student,
        'courseTeachers' => $courseTeachers,
        'academic_session_id' => $selection['academic_session_id'],
        'term' => $selection['term'],
        'for_mid_term' => $selection['for_mid_term'],
        'assessmentGroups' => $student
          ? Assessment::getAssessmentGroups(
            $selection['term'],
            $student->classification_id
          )
          : ['fullTerm' => [], 'midTerm' => []],
        'showExamInput' => [
          'fullTerm' => $setting->shouldDisplayExamResults(null, false),
          'midTerm' => $setting->shouldDisplayExamResults(null, true)
        ]
      ]
    );
  }

  public function store(
    RecordStudentSubjectsResultRequest $request,
    Institution $institution
  ) {
    $data = $request->validated();
    $baseData = collect($data)
      ->only(['academic_session_id', 'term', 'for_mid_term', 'student_id'])
      ->toArray();

    DB::beginTransaction();
    foreach ($data['result'] as $result) {
      /** @var CourseTeacher $courseTeacher */
      $courseTeacher = CourseTeacher::query()
        ->with('classification')
        ->findOrFail($result['course_teacher_id']);
      RecordCourseResult::run(
        [
          'institution_id' => $institution->id,
          ...$baseData,
          ...collect($result)
            ->except('course_teacher_id')
            ->all()
        ],
        $courseTeacher
      );

      EvaluateCourseResultForClass::run(
        $courseTeacher->classification,
        $courseTeacher->course_id,
        $data['academic_session_id'],
        $data['term'],
        $data['for_mid_term']
      );
    }
    DB::commit();

    return response()->json(['ok' => true]);
  }

  private function selection(Request $request, SettingsHandler $setting): array
  {
    return [
      'academic_session_id' => $request->integer(
        'academic_session_id',
        $setting->getCurrentAcademicSession()
      ),
      'term' => $request->input('term', $setting->getCurrentTerm()),
      'for_mid_term' => $setting->usesMidTerm()
        ? $this->getNullableBoolean($request, 'for_mid_term')
        : false
    ];
  }

  private function getNullableBoolean(Request $request, string $key): ?bool
  {
    if (!$request->has($key)) {
      return null;
    }

    return $request->boolean($key);
  }

  private function selectedStudent(Request $request): ?Student
  {
    if (!$request->filled('student_id')) {
      return null;
    }

    return Student::query()
      ->with('user', 'classification')
      ->whereHas(
        'classification',
        fn($query) => $query->where('institution_id', currentInstitution()->id)
      )
      ->find($request->integer('student_id'));
  }

  private function studentCourseTeachers(Student $student)
  {
    $query = CourseTeacher::query()
      ->select('course_teachers.*')
      ->join('courses', 'courses.id', 'course_teachers.course_id')
      ->where('course_teachers.classification_id', $student->classification_id)
      ->orderBy('courses.order')
      ->orderBy('courses.title');

    if (!currentInstitutionUser()->isAdmin()) {
      $query->where('course_teachers.user_id', currentUser()->id);
    }

    return $query;
  }

  private function attachExistingResults(
    $courseTeachers,
    Student $student,
    array $selection
  ): void {
    $results = CourseResult::query()
      ->where('student_id', $student->id)
      ->where('classification_id', $student->classification_id)
      ->where('academic_session_id', $selection['academic_session_id'])
      ->where('term', $selection['term'])
      ->whereIn('for_mid_term', [false, true])
      ->whereIn('course_id', $courseTeachers->pluck('course_id'))
      ->whereIn('teacher_user_id', $courseTeachers->pluck('user_id'))
      ->get()
      ->groupBy(
        fn(CourseResult $result) => implode(':', [
          $result->course_id,
          $result->teacher_user_id
        ])
      );

    foreach ($courseTeachers as $courseTeacher) {
      $courseTeacher->setRelation(
        'courseResults',
        $results
          ->get(
            implode(':', [$courseTeacher->course_id, $courseTeacher->user_id]),
            collect()
          )
          ->values()
      );
    }
  }
}
