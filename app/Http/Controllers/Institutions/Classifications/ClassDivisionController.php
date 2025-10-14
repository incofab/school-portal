<?php
namespace App\Http\Controllers\Institutions\Classifications;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\ClassDivision;

class ClassDivisionController extends Controller
{
  public function index(Institution $institution)
  {
    $query = ClassDivision::query();
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

  function edit(Institution $institution, ClassDivision $classDivision)
  {
    return inertia('institutions/class-divisions/create-edit-class-divisions', [
      'classDivision' => $classDivision
    ]);
  }

  public function update(Institution $institution, ClassDivision $classDivision)
  {
    $data = request()->validate(ClassDivision::createRule($classDivision));

    $classDivision->fill($data)->save();
    return $this->ok();
  }

  function search(Institution $institution)
  {
    return response()->json([
      'result' => ClassDivision::query()
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
    ClassDivision $classDivision
  ) {
    // abort_if(
    //   $classDivision->classifications()->count() > 0,
    //   403,
    //   'This class division contains some classes'
    // );
    $classDivision->delete();
    return $this->ok();
  }
}
