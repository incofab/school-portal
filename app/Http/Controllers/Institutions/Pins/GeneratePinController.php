<?php
namespace App\Http\Controllers\Institutions\Pins;

use App\Http\Controllers\Controller;
use App\Models\Pin;
use App\Models\PinGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** @deprecated All methods moved to App\Http\Controllers\Pins\PinGeneratorController */
class GeneratePinController extends Controller
{
  function create()
  {
    return inertia('managers/pins/generate-pin', [
      'reference' => Str::orderedUuid()
    ]);
  }

  function store(Request $request)
  {
    $data = $request->validate([
      'num_of_pins' => ['required', 'integer'],
      'institution_id' => ['required', 'exists:institutions,id'],
      'reference' => ['required', 'unique:pin_generators,reference']
    ]);

    $pinGenerator = PinGenerator::query()->create([
      ...$data,
      'user_id' => currentUser()->id
    ]);

    for ($i = 0; $i < $data['num_of_pins']; $i++) {
      Pin::query()->create([
        'pin' => Pin::generatePin(),
        'institution_id' => $data['institution_id'],
        'pin_generator_id' => $pinGenerator->id
      ]);
    }

    return $this->ok(['pinGenerator' => $pinGenerator]);
  }
}
