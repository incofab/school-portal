<?php
namespace App\Http\Controllers\API\OfflineMock;

use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Student;
use App\Support\MorphMap;
use DB;
use Illuminate\Http\Request;

class ExamController extends Controller
{
  function uploadEventResult(Institution $institution, Request $request)
  {
    $request->validate([
      'exams' => ['required', 'array'],
      'exams.*.event_id' => ['required', 'integer'],
      'exams.*.exam_no' => ['required', 'string'],
      'exams.*.attempts' => ['nullable', 'array'],
      'exams.*.time_remaining' => ['nullable', 'string'],
      'exams.*.start_time' => ['nullable', 'string'],
      'exams.*.pause_time' => ['nullable', 'string'],
      'exams.*.end_time' => ['nullable', 'string'],
      'exams.*.status' => ['nullable', 'string'],
      'exams.*.num_of_questions' => ['nullable', 'integer'],

      'exams.*.exam_courses' => ['required', 'array', 'min:1'],
      'exams.*.exam_courses.*.course_session_id' => ['required', 'integer'],
      'exams.*.exam_courses.*.score' => ['nullable', 'numeric'],
      'exams.*.exam_courses.*.status' => ['nullable', 'string'],
      'exams.*.exam_courses.*.num_of_questions' => ['nullable', 'integer']
    ]);
    $exams = $request->exams;

    DB::beginTransaction();

    foreach ($exams as $exam) {
      if (empty($exam['attempts'])) {
        continue;
      }
      $code = explode('-', $exam['exam_no'])[1] ?? null;
      $student = Student::query()
        ->where('code', $code)
        ->first();
      if (!$student) {
        continue;
      }
      $createdExam = Exam::query()->updateOrCreate(
        [
          'institution_id' => $institution->id,
          'event_id' => $exam['event_id'],
          'exam_no' => $exam['exam_no'],
          'examable_id' => $student->id,
          'examable_type' => $student?->getMorphClass()
        ],
        collect($exam)
          ->only(
            'num_of_questions',
            'score',
            'attempts',
            'time_remaining',
            'start_time',
            'pause_time',
            'end_time',
            'status'
          )
          ->toArray()
      );
      $examCourses = $exam['exam_courses'];
      foreach ($examCourses as $examCourse) {
        ExamCourseable::query()->updateOrCreate(
          [
            'exam_id' => $createdExam->id,
            'courseable_type' => MorphMap::key(CourseSession::class),
            'courseable_id' => $examCourse['course_session_id']
          ],
          collect($examCourse)
            ->only('score', 'status', 'num_of_questions')
            ->toArray()
        );
      }
    }

    DB::commit();

    return $this->successApiRes([], 'Exam records updated');
  }
}
