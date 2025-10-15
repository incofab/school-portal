<?php
namespace App\Http\Controllers\Institutions\Classifications;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\ClassDivision;

use App\Models\Classification;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;

class ClassDivisionController extends Controller
{
  public function index(Institution $institution)
  {
    $query = ClassDivision::query()
      ->withCount('classifications')
      ->with('classifications');
    return inertia('institutions/class-divisions/list-class-divisions', [
      'classdivisions' => paginateFromRequest($query)
    ]);
  }

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate(ClassDivision::createRule());

    /** @var ClassDivision $classDivision */
    $classDivision = $institution->classDivisions()->create(
      collect($data)
        ->except('classification_ids')
        ->toArray()
    );

    if ($request->classification_ids) {
      $classDivision->classifications()->sync($request->classification_ids);
    }

    return $this->ok();
  }

  public function update(Institution $institution, ClassDivision $classDivision)
  {
    $data = request()->validate(ClassDivision::createRule($classDivision));

    $classDivision
      ->fill(
        collect($data)
          ->except('classification_ids')
          ->toArray()
      )
      ->save();
    return $this->ok();
  }

  function search(Request $request, Institution $institution)
  {
    return response()->json([
      'result' => ClassDivision::query()
        ->when(
          $request->search,
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->orderBy('title')
        ->get()
    ]);
  }

  public function storeClassification(
    Request $request,
    Institution $institution,
    ClassDivision $classDivision
  ) {
    info($request->all());
    $request->validate([
      'classification_ids' => ['required', 'array', 'min:1'],
      'classification_ids.*' => [
        'integer',
        'distinct',
        new ValidateExistsRule(Classification::class)
      ]
    ]);

    $classDivision
      ->classifications()
      ->syncWithoutDetaching($request->classification_ids);

    return $this->ok();
  }

  public function destroyClassification(
    Institution $institution,
    ClassDivision $classDivision,
    Classification $classification
  ) {
    $classDivision->classifications()->detach($classification->id);

    return $this->ok();
  }

  public function destroy(
    Institution $institution,
    ClassDivision $classDivision
  ) {
    // abort_if(
    //   $classDivision->classifications()->count() > 0,
    //   403,
    //   'This class division contains some classes'
    // );
    $classDivision->classifications()->detach();
    $classDivision->forceDelete();
    return $this->ok();
  }
}
