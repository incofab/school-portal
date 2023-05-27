<?php
namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use Illuminate\Validation\Rule;

class ClassificationController extends Controller
{
  function index()
  {
    $query = Classification::query();
    return inertia('institutions/staff/list-classifications', [
      'classifications' => paginateFromRequest($query)
    ]);
  }

  function create()
  {
    return inertia('institutions/staff/create-classification');
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

  function edit(Classification $classification)
  {
    return inertia('institutions/staff/create-classification', [
      'classification' => $classification
    ]);
  }

  function update(Classification $classification)
  {
    abort_unless(
      $classification->institution_id === currentInstitution()->id,
      403
    );
    $data = request()->validate([
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classifications', 'title')
          ->where('institution_id', currentInstitution()->id)
          ->ignore($classification->id, 'id')
      ],
      'description' => ['nullable', 'string']
    ]);

    $classification->fill($data)->save();
    return $this->ok();
  }

  function destroy(Classification $classification)
  {
    abort_unless(
      $classification->institution_id === currentInstitution()->id,
      403
    );

    $classification->delete();
    return $this->ok();
  }
}
