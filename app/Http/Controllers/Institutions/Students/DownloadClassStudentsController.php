<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Actions\CourseResult\DownloadClassStudentsSheet;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Support\UITableFilters\StudentUITableFilters;
use Illuminate\Http\Request;
use Storage;

class DownloadClassStudentsController extends Controller
{
  public function __invoke(
    Institution $institution,
    Classification $classification,
    Request $request
  ) {
    $query = Student::query()->select('students.*');
    StudentUITableFilters::make(
      [...$request->all(), 'classification' => $classification->id],
      $query
    )->filterQuery();

    $students = $query
      ->with('user')
      ->oldest('students.id')
      ->get();

    $excelWriter = DownloadClassStudentsSheet::run($students);

    $filename = "{$classification->title}-students.xlsx";
    $filename = str_replace(['/', ' '], ['_', '-'], $filename);
    // $path = 'result-record-sheet.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename, $filename);
  }
}
