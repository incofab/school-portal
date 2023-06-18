<?php
namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\Institution;
use Illuminate\Validation\Rule;

class ClassificationController extends Controller
{
  function index()
  {
    $query = Classification::query()->withCount('students');
    return inertia('institutions/classifications/list-classifications', [
      'classifications' => paginateFromRequest($query)
    ]);
  }

  function search()
  {
    return response()->json([
      'result' => Classification::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->orderBy('title')
        ->get()
    ]);
  }

  function create()
  {
    return inertia('institutions/classifications/create-edit-classification');
  }

  function store()
  {
    $data = request()->validate([
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classifications', 'title')->where(
          'institution_id',
          currentInstitution()->id
        )
      ],
      'description' => ['nullable', 'string']
    ]);

    currentInstitution()
      ->classifications()
      ->create($data);
    return $this->ok();
  }

  function edit(Institution $institution, Classification $classification)
  {
    return inertia('institutions/classifications/create-edit-classification', [
      'classification' => $classification
    ]);
  }

  function update(Institution $institution, Classification $classification)
  {
    $data = request()->validate([
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classifications', 'title')
          ->where('institution_id', $institution->id)
          ->ignore($classification->id, 'id')
      ],
      'description' => ['nullable', 'string']
    ]);

    $classification->fill($data)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, Classification $classification)
  {
    $classification->delete();
    return $this->ok();
  }
}
