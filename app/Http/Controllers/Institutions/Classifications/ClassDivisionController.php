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

  function store(Institution $institution)
  {
    $data = request()->validate(ClassDivision::createRule());

    $institution->classDivisions()->create($data);
    return $this->ok();
  }

  public function update(Institution $institution, ClassDivision $classDivision)
  {
    $data = request()->validate(ClassDivision::createRule($classDivision));

    $classDivision->fill($data)->save();
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
    $request->validate([
      'classification_ids' => [
        'required',
        'array',
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
    abort_if(
      $classDivision->classifications()->count() > 0,
      403,
      'This class division contains some classes'
    );
    $classDivision->delete();
    return $this->ok();
  }
}
