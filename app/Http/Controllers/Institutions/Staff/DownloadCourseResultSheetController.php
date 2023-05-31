<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\DownloadCourseResultSheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadCourseResultSheetRequest;
use App\Models\CourseResult;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use Storage;

class DownloadCourseResultSheetController extends Controller
{
  public function __invoke(DownloadCourseResultSheetRequest $request)
  {
    $query = CourseResult::query()->select('course_results.*');
    CourseResultsUITableFilters::make($request->all(), $query)->filterQuery();

    $students = $query
      ->with('user')
      ->oldest('course_results.student_id')
      ->get();

    $excelWriter = DownloadCourseResultSheet::run($students);

    $filename =
      "{$request->courseObj->title}-{$request->classificationObj->title}" .
      "-{$request->term}-{$request->academicSessionObj->title}-results.xlsx";

    $filename = str_replace(['/', ' '], ['_', '-'], $filename);

    // $path = 'result-record-sheet.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename, $filename);
  }
}
