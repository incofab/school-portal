<?php

namespace App\Http\Controllers\CCD;

use App\Actions\ExportCourse;
use App\Actions\UploadCourseContent;
use App\Enums\Audit\ActivityLogCategory;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Institution;
use App\Support\Audit\AcademicActivityLogger;
use Illuminate\Http\Request;

class UploadContentController extends Controller
{
  public function exportCourse(Institution $institution, Course $course)
  {
    app(AcademicActivityLogger::class)->workflowEvent(
      $institution,
      'question_bank.exported',
      ActivityLogCategory::Course,
      'exported_question_bank',
      'Question bank content exported.',
      [
        'course_id' => $course->id,
        'course_title' => $course->title,
        'course_code' => $course->code
      ],
      $course
    );
    ExportCourse::run($course);
    exit('Done');
  }

  public function uploadContentView(Institution $institution, Course $course)
  {
    return view('ccd/courses/upload-content', [
      'course' => $course
    ]);
  }

  public function uploadContent(
    Institution $institution,
    Course $course,
    Request $request
  ) {
    $request->validate([
      'content' => ['required', 'mimes:zip', 'file', 'max:' . 20 * 1024]
    ]);

    UploadCourseContent::run($course, $request->file('content'));

    app(AcademicActivityLogger::class)->workflowEvent(
      $institution,
      'question_bank.imported',
      ActivityLogCategory::Course,
      'imported_question_bank',
      'Question bank content imported.',
      [
        'course_id' => $course->id,
        'course_title' => $course->title,
        'course_code' => $course->code,
        'file_name' => $request->file('content')->getClientOriginalName()
      ],
      $course
    );

    return redirect(instRoute('courses.index', $course->exam_content_id))->with(
      [
        'success' => 'Content uploaded successfully'
      ]
    );
  }
}
