<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\DownloadCourseResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadCourseResultSheetRequest;
use App\Models\CourseResult;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use Illuminate\Support\Facades\Storage;

class DownloadCourseResultSheetController extends Controller
{
  public function __invoke(DownloadCourseResultSheetRequest $request)
  {
    $query = CourseResult::query()->select('course_results.*');
    CourseResultsUITableFilters::make($request->all(), $query)->filterQuery();

    $courseResults = $query
      ->with('student.user')
      ->oldest('course_results.student_id')
      ->get();

    $excelWriter = DownloadCourseResult::run(
      $courseResults,
      $request->term,
      $request->forMidTerm
    );

    $filename =
      "{$request->courseObj->title}-{$request->classificationObj->title}" .
      "-{$request->term}-{$request->academicSessionObj->title}-results.xlsx";

    $filename = str_replace(['/', ' '], ['_', '-'], $filename);

    // $path = 'result-record-sheet.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename, $filename);
  }
}
