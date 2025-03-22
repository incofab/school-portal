<?php

namespace App\Http\Controllers\Institutions\Pins;

use App\Actions\CourseResult\DownloadPins;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\PinGenerator;
use Storage;

/** @deprecated No longer needed, code moved to PinGeneratorController */
class PinController extends Controller
{
  public function index(Institution $institution, PinGenerator $pinGenerator)
  {
    // $query = $pinGenerator->pins()->getQuery();
    $pins = $pinGenerator->pins()->get();
    return inertia('institutions/pins/display-pins', [
      'pins' => $pins,
      'resultCheckerUrl' => $institution->website
        ? "{$institution->website}/result"
        : route('activate-term-result.create')
    ]);
    // $query = PinUITableFilters::make($request->all(), $query)
    //   ->filterQuery()
    //   ->getQuery()
    //   ->with('institution');
    // return Inertia::render('institutions/pins/list-pins', [
    //   'pins' => paginateFromRequest($query->latest('id')),
    //   'pinGenerator' => $pinGenerator?->load('user', 'institution')
    // ]);
  }

  public function downloadPins(
    Institution $institution,
    PinGenerator $pinGenerator
  ) {
    $pins = $pinGenerator->pins()->get();

    $excelWriter = DownloadPins::run($pins);

    $filename = "{$institution->name}-pin-{$pinGenerator->id}-prints.xlsx";

    $filename = str_replace(['/', ' '], ['_', '-'], $filename);

    // $path = 'result-record-sheet.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename);
  }
}
