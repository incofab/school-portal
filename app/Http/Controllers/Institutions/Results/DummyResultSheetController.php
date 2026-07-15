<?php

namespace App\Http\Controllers\Institutions\Results;

use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Models\Student;
use App\Models\TermDetail;
use App\Models\TermResult;
use App\Models\User;
use App\Support\SettingsHandler;
use Illuminate\Support\Collection;

class DummyResultSheetController extends Controller
{
  public function __invoke(Institution $institution)
  {
    $institution->loadMissing('institutionSettings', 'institutionGroup');

    $settingsHandler = SettingsHandler::makeFromInstitution($institution);
    $term = $settingsHandler->getCurrentTerm(TermType::First->value);
    $forMidTerm = $settingsHandler->isOnMidTerm();
    $academicSession = $this->academicSession($settingsHandler);
    $classification = $this->classification($institution);
    $assessments = $this->assessments($term, $forMidTerm, $classification);
    $courses = $this->courses($institution);
    $courseResults = $this->courseResults(
      $institution,
      $classification,
      $academicSession,
      $term,
      $forMidTerm,
      $assessments,
      $courses
    );
    $average = round($courseResults->avg('result'), 2);
    $totalScore = round($courseResults->sum('result'), 2);
    $student = $this->student($classification);
    $termResult = $this->termResult(
      $institution,
      $student,
      $classification,
      $academicSession,
      $term,
      $forMidTerm,
      $totalScore,
      $average
    );
    $classResultInfo = $this->classResultInfo(
      $institution,
      $classification,
      $academicSession,
      $term,
      $forMidTerm,
      $courseResults->count(),
      $average
    );
    $termDetail = $this->termDetail(
      $institution,
      $academicSession,
      $term,
      $forMidTerm
    );

    $viewData = [
      'institution' => $institution,
      'courseResults' => $courseResults,
      'student' => $student,
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term,
      'termResult' => $termResult,
      'classResultInfo' => $classResultInfo,
      'courseResultInfoData' => $this->courseResultInfoData(
        $institution,
        $classification,
        $academicSession,
        $term,
        $forMidTerm,
        $courseResults
      ),
      'resultDetails' => [
        ['label' => "Student's Total Score", 'value' => $totalScore],
        [
          'label' => 'Maximum Total Score',
          'value' => $courseResults->count() * 100
        ],
        ['label' => "Student's Average Score", 'value' => $average],
        ['label' => 'Class Average Score', 'value' => $average - 3]
      ],
      'assessments' => $assessments,
      'resultCommentTemplate' => ResultCommentTemplate::getTemplate(
        $classification,
        $forMidTerm
      ),
      'termDetail' => $termDetail,
      'showExamResult' => $settingsHandler->shouldDisplayExamResults(
        $termDetail,
        $forMidTerm
      ),
      'learningEvaluations' => $institution
        ->learningEvaluations()
        ->with('learningEvaluationDomain')
        ->orderBy('learning_evaluation_domain_id')
        ->get(),
      'signed_url' => null
    ];

    return inertia(
      "institutions/result-sheets/{$settingsHandler->getResultTemplate()}",
      $viewData
    );
  }

  private function academicSession(
    SettingsHandler $settingsHandler
  ): AcademicSession {
    $academicSession = AcademicSession::query()->find(
      $settingsHandler->getCurrentAcademicSession(null)
    );

    return $academicSession ??
      new AcademicSession([
        'id' => 0,
        'title' => '2026/2027',
        'is_active' => true,
        'order_index' => 1
      ]);
  }

  private function classification(Institution $institution): Classification
  {
    return Classification::query()
      ->where('institution_id', $institution->id)
      ->oldest('id')
      ->first() ??
      new Classification([
        'id' => 0,
        'institution_id' => $institution->id,
        'title' => 'Basic 5',
        'has_equal_subjects' => true
      ]);
  }

  /** @return Collection<int, Assessment> */
  private function assessments(
    string $term,
    bool $forMidTerm,
    Classification $classification
  ): Collection {
    $assessments = Assessment::getAssessments(
      $term,
      $forMidTerm,
      $classification
    );
    if ($assessments->isNotEmpty()) {
      return $assessments->values();
    }

    return collect([
      $this->dummyAssessment(1, 'first_ca', 20),
      $this->dummyAssessment(2, 'second_ca', 20)
    ]);
  }

  private function dummyAssessment(
    int $id,
    string $rawTitle,
    int $max
  ): Assessment {
    $assessment = new Assessment();
    $assessment->setRawAttributes(
      ['id' => $id, 'title' => $rawTitle, 'max' => $max],
      true
    );

    return $assessment;
  }

  /** @return Collection<int, Course> */
  private function courses(Institution $institution): Collection
  {
    $courses = Course::query()
      ->where('institution_id', $institution->id)
      ->orderedByCourseOrder()
      ->limit(14)
      ->get();

    $fallbackTitles = collect([
      'Mathematics',
      'English Language',
      'Basic Science',
      'Social Studies',
      'Civic Education',
      'Computer Studies',
      'Agricultural Science',
      'Creative Arts',
      'Business Studies',
      'Physical Education',
      'French',
      'Literature',
      'Economics',
      'Geography'
    ]);

    $nextId = 100000;
    while ($courses->count() < 10) {
      $title = $fallbackTitles
        ->reject(fn(string $fallback) => $courses->contains('title', $fallback))
        ->first();
      if (!$title) {
        break;
      }

      $courses->push(
        new Course([
          'id' => $nextId++,
          'institution_id' => $institution->id,
          'title' => $title,
          'code' => (string) str($title)
            ->upper()
            ->replace(' ', '')
            ->substr(0, 4)
        ])
      );
    }

    return $courses->take(14)->values();
  }

  private function student(Classification $classification): Student
  {
    $user = new User([
      'id' => 0,
      'first_name' => 'Amina',
      'other_names' => 'Demo',
      'last_name' => 'Bello',
      'gender' => 'female',
      'email' => 'dummy.student@example.test'
    ]);

    $student = new Student([
      'id' => 0,
      'user_id' => 0,
      'classification_id' => $classification->id,
      'code' => 'DEMO/001'
    ]);
    $student->setRelation('user', $user);
    $student->setRelation('classification', $classification);

    return $student;
  }

  private function termResult(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm,
    float $totalScore,
    float $average
  ): TermResult {
    $termResult = new TermResult([
      'id' => 0,
      'institution_id' => $institution->id,
      'student_id' => $student->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => $term,
      'for_mid_term' => $forMidTerm,
      'total_score' => $totalScore,
      'average' => $average,
      'position' => 3,
      'class_group_position' => 5,
      'teacher_comment' =>
        'A consistent performance with room for steady growth.',
      'principal_comment' => 'A pleasing result. Keep working hard.',
      'height' => '1.42m',
      'weight' => '36kg',
      'attendance_count' => 58,
      'is_activated' => true
    ]);
    $termResult->setRelation('classification', $classification);
    $termResult->setRelation('institution', $institution);
    $termResult->setRelation('student', $student);
    $termResult->setRelation('academicSession', $academicSession);

    return $termResult;
  }

  private function classResultInfo(
    Institution $institution,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm,
    int $courseCount,
    float $average
  ): ClassResultInfo {
    return new ClassResultInfo([
      'id' => 0,
      'institution_id' => $institution->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => $term,
      'for_mid_term' => $forMidTerm,
      'num_of_students' => 32,
      'num_of_courses' => $courseCount,
      'total_score' => $average * $courseCount,
      'average' => round($average - 3, 2),
      'max_score' => 94,
      'min_score' => 42,
      'max_obtainable_score' => $courseCount * 100,
      'next_term_resumption_date' => now()
        ->addWeeks(5)
        ->toDateString()
    ]);
  }

  private function termDetail(
    Institution $institution,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm
  ): TermDetail {
    return TermDetail::query()
      ->where('institution_id', $institution->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm)
      ->first() ??
      new TermDetail([
        'institution_id' => $institution->id,
        'academic_session_id' => $academicSession->id,
        'term' => $term,
        'for_mid_term' => $forMidTerm,
        'start_date' => now()
          ->subWeeks(11)
          ->toDateString(),
        'end_date' => now()->toDateString(),
        'next_term_resumption_date' => now()
          ->addWeeks(5)
          ->toDateString()
      ]);
  }

  /**
   * @param Collection<int, Assessment> $assessments
   * @param Collection<int, Course> $courses
   * @return Collection<int, CourseResult>
   */
  private function courseResults(
    Institution $institution,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm,
    Collection $assessments,
    Collection $courses
  ): Collection {
    return $courses
      ->values()
      ->map(function (Course $course, int $index) use (
        $institution,
        $classification,
        $academicSession,
        $term,
        $forMidTerm,
        $assessments
      ) {
        $assessmentValues = [];
        foreach ($assessments as $assessment) {
          $assessmentValues[$assessment->raw_title] = rand(
            max(1, (int) floor($assessment->max * 0.55)),
            max(1, (int) $assessment->max)
          );
        }

        $assessmentTotal = array_sum($assessmentValues);
        $exam = rand(35, 58);
        $result = min(100, $assessmentTotal + $exam);
        $courseResult = new CourseResult([
          'id' => $index + 1,
          'institution_id' => $institution->id,
          'course_id' => $course->id,
          'classification_id' => $classification->id,
          'academic_session_id' => $academicSession->id,
          'student_id' => 0,
          'term' => $term,
          'for_mid_term' => $forMidTerm,
          'assessment_values' => $assessmentValues,
          'exam' => $exam,
          'result' => $result,
          'grade' => $result >= 70 ? 'A' : ($result >= 60 ? 'B' : 'C'),
          'position' => ($index % 5) + 1
        ]);
        $courseResult->setRelation('course', $course);

        return $courseResult;
      });
  }

  /**
   * @param Collection<int, CourseResult> $courseResults
   * @return array<int, CourseResultInfo>
   */
  private function courseResultInfoData(
    Institution $institution,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm,
    Collection $courseResults
  ): array {
    $data = [];

    foreach ($courseResults as $courseResult) {
      $data[$courseResult->course_id] = new CourseResultInfo([
        'id' => $courseResult->course_id,
        'institution_id' => $institution->id,
        'course_id' => $courseResult->course_id,
        'classification_id' => $classification->id,
        'academic_session_id' => $academicSession->id,
        'term' => $term,
        'for_mid_term' => $forMidTerm,
        'num_of_students' => 32,
        'total_score' => $courseResult->result * 32,
        'average' => max(35, $courseResult->result - rand(2, 9)),
        'min_score' => max(20, $courseResult->result - rand(18, 35)),
        'max_score' => min(100, $courseResult->result + rand(4, 13)),
        'max_obtainable_score' => 100
      ]);
    }

    return $data;
  }
}
