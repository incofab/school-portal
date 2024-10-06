<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\DownloadPins;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Pin;
use App\Models\PinPrint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Storage;

class PinPrintController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Request $request)
  {
    $query = PinPrint::query()->with('user');
    return Inertia::render('institutions/pins/list-printed-pins', [
      'pinPrints' => paginateFromRequest($query->latest('pin_prints.id'))
    ]);
  }

  public function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'num_of_pins' => ['required', 'integer', 'min:1'],
      'comment' => ['nullable', 'string'],
      'reference' => ['required', 'unique:pin_prints,reference']
    ]);

    $numOfPins = $data['num_of_pins'];

    $pins = Pin::query()
      ->used(false)
      ->printed(false)
      ->take($numOfPins)
      ->get();

    abort_unless(
      $pins->count() >= $numOfPins,
      Response::HTTP_BAD_REQUEST,
      'Insufficient pins available'
    );

    $pinPrint = $institution
      ->pinPrints()
      ->create([...$data, 'user_id' => currentUser()->id]);

    Pin::query()
      ->whereIn('id', $pins->pluck('id'))
      ->update(['pin_print_id' => $pinPrint->id]);

    return $this->ok(['pinPrint' => $pinPrint]);
  }

  public function show(
    Request $request,
    Institution $institution,
    PinPrint $pinPrint
  ) {
    $pins = $pinPrint->pins()->get();

    return inertia('institutions/pins/display-pins', [
      'pins' => $pins,
      'resultCheckerUrl' => $institution->website
        ? "{$institution->website}/result"
        : route('activate-term-result.create')
    ]);
  }

  public function downloadPins(
    Request $request,
    Institution $institution,
    PinPrint $pinPrint
  ) {
    $pins = $pinPrint->pins()->get();

    $excelWriter = DownloadPins::run($pins);

    $filename = "{$institution->name}-pin-{$pinPrint->id}-prints.xlsx";

    $filename = str_replace(['/', ' '], ['_', '-'], $filename);

    // $path = 'result-record-sheet.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename);
  }
}
