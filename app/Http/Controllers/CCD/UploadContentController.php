<?php
namespace App\Http\Controllers\CCD;

use App\Actions\ExportCourse;
use App\Actions\UploadCourseContent;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Institution;
use Illuminate\Http\Request;

class UploadContentController extends Controller
{
  function exportCourse(Institution $institution, Course $course)
  {
    ExportCourse::run($course);
    die('Done');
  }

  function uploadContentView(Institution $institution, Course $course)
  {
    return view('ccd/courses/upload-content', [
      'course' => $course
    ]);
  }

  function uploadContent(
    Institution $institution,
    Course $course,
    Request $request
  ) {
    $request->validate([
      'content' => ['required', 'mimes:zip', 'file', 'max:' . 20 * 1024]
    ]);

    UploadCourseContent::run($course, $request->file('content'));

    return redirect(instRoute('courses.index', $course->exam_content_id))->with(
      [
        'success' => 'Content uploaded successfully'
      ]
    );
  }
}
