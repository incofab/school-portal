<?php
namespace App\Actions\CourseResult;

use App\Models\CourseTeacher;
use App\Models\CourseResult;

class RecordCourseResult
{
  public static function run($data, CourseTeacher $courseTeacher)
  {
    $data['course_id'] = $courseTeacher->course_id;
    $data['teacher_user_id'] = $courseTeacher->user_id;
    $result = $data['result'];
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
      [...$data, 'grade' => GetGrade::run($result), 'result_max' => null]
    );
  }
}
