<?php
namespace App\Http\Controllers\Institutions\Classifications;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Validation\Rule;
use App\Models\ClassificationGroup;

class ClassificationGroupController extends Controller
{
  public function index(Institution $institution)
  {
    $query = ClassificationGroup::query()->withCount('classifications');
    return inertia(
      'institutions/classification-groups/list-classification-groups',
      [
        'classificationgroups' => paginateFromRequest($query)
      ]
    );
  }

  public function create(Institution $institution)
  {
    return inertia(
      'institutions/classification-groups/create-edit-classification-groups'
    );
  }

  function store(Institution $institution)
  {
    $data = request()->validate([
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classification_groups', 'title')->where(
          'institution_id',
          $institution->id
        )
      ]
    ]);

    $institution->classificationGroups()->create($data);
    return $this->ok();
  }

  function edit(
    Institution $institution,
    ClassificationGroup $classificationGroup
  ) {
    return inertia(
      'institutions/classification-groups/create-edit-classification-groups',
      [
        'classificationGroup' => $classificationGroup
      ]
    );
  }

  public function update(
    Institution $institution,
    ClassificationGroup $classificationGroup
  ) {
    $data = request()->validate([
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classification_groups', 'title')
          ->where('institution_id', $institution->id)
          ->ignore($classificationGroup->id, 'id')
      ]
    ]);

    $classificationGroup->fill($data)->save();
    return $this->ok();
  }

  function search(Institution $institution)
  {
    return response()->json([
      'result' => ClassificationGroup::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->orderBy('title')
        ->get()
    ]);
  }

  public function destroy(
    Institution $institution,
    ClassificationGroup $classificationGroup
  ) {
    abort_if(
      $classificationGroup->classifications()->count() > 0,
      403,
      'This class group contains some classes'
    );
    $classificationGroup->delete();
    return $this->ok();
  }
}
