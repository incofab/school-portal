<?php

namespace App\Http\Controllers\Institutions\Pins;

use App\Actions\CourseResult\DownloadPins;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Pin;
use App\Models\PinGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Storage;

class PinGeneratorController extends Controller
{
  public function index(Institution $institution)
  {
    $query = PinGenerator::query()->with('institution', 'user');

    return Inertia::render('institutions/pins/list-pin-generators', [
      'pinGenerators' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function create(Institution $institution)
  {
    return inertia('institutions/pins/generate-pin', []);
  }

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate([
      'num_of_pins' => ['required', 'integer'],
      'comment' => ['nullable', 'string', 'max:255'],
      'reference' => ['required', 'unique:pin_generators,reference']
    ]);

    $pinGenerator = $institution
      ->pinGenerators()
      ->create([...$data, 'user_id' => currentUser()->id]);

    for ($i = 0; $i < $data['num_of_pins']; $i++) {
      $institution->pins()->create([
        'pin' => Pin::generatePin(),
        'pin_generator_id' => $pinGenerator->id
      ]);
    }

    return $this->ok(['pinGenerator' => $pinGenerator]);
  }

  public function show(Institution $institution, PinGenerator $pinGenerator)
  {
    $pins = $pinGenerator->pins()->get();
    return inertia('institutions/pins/display-pins', [
      'pins' => $pins,
      'resultCheckerUrl' => $institution->website
        ? "{$institution->website}/result"
        : route('activate-term-result.create')
    ]);
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
