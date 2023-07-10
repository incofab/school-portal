<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\DownloadResultRecordingSheet;
use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadResultRecordingSheetRequest;
use Illuminate\Support\Facades\Storage;

class DownloadResultRecordingSheetController extends Controller
{
  public function __invoke(DownloadResultRecordingSheetRequest $request)
  {
    $excelWriter = DownloadResultRecordingSheet::run(
      $request->classificationObj,
      $request->academicSessionObj,
      $request->term,
      $request->forMidTerm
    );

    $filename =
      "{$request->classificationObj->title}-{$request->term}" .
      ($request->forMidTerm ? '-mid-term' : '') .
      "-{$request->academicSessionObj->title}-results.xlsx";

    $filename = str_replace(['/', ' '], ['_', '-'], $filename);

    // $path = 'result-record-sheet.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename, $filename);
  }
}
