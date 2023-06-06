<?php
namespace App\Actions\CourseResult;

use App\Models\CourseTeacher;
use App\Models\CourseResult;

class RecordCourseResult
{
  public static function run(
    $data,
    CourseTeacher $courseTeacher,
    bool $processCourseResultForClass = false
  ) {
    $result =
      $data['first_assessment'] + $data['second_assessment'] + $data['exam'];
    $data['course_id'] = $courseTeacher->course_id;
    $data['teacher_user_id'] = $courseTeacher->user_id;
    $data['classification_id'] = $courseTeacher->classification_id;

    CourseResult::query()->updateOrCreate(
      [
        ...collect($data)
          ->only([
            'course_id',
            'student_id',
            'classification_id',
            'academic_session_id',
            'term'
          ])
          ->toArray()
      ],
      [...$data, 'result' => $result, 'grade' => GetGrade::run($result)]
    );

    if ($processCourseResultForClass) {
      EvaluateCourseResultForClass::run(
        $courseTeacher->classification,
        $courseTeacher->course_id,
        $data['academic_session_id'],
        $data['term']
      );
    }
  }
}
