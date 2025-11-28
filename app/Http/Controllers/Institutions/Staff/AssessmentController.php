<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\FullTermType;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Institution;
use App\Support\UITableFilters\AssessmentUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AssessmentController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  function search(Institution $institution, Request $request)
  {
    $query = AssessmentUITableFilters::make(
      $request->all(),
      Assessment::query()
    )
      ->filterQuery()
      ->getQuery();

    return response()->json([
      'result' => paginateFromRequest($query)
    ]);
  }

  function index(
    Request $request,
    Institution $institution,
    ?Assessment $assessment = null
  ) {
    $query = AssessmentUITableFilters::make(
      $request->all(),
      Assessment::query()
    )
      ->filterQuery()
      ->getQuery();

    return Inertia::render('institutions/assessments/create-edit-assessment', [
      'assessments' => $query->with('classifications')->get(),
      'assessment' => $assessment?->load('classifications'),
      'classifications' => \App\Models\Classification::all()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate(Assessment::createRule());

    $assessment = $institution->assessments()->updateOrCreate(
      collect($data)
        ->only(['term', 'for_mid_term', 'title'])
        ->toArray(),
      collect($data)
        ->except('classification_ids')
        ->toArray()
    );

    $assessment->classifications()->sync($data['classification_ids'] ?? []);

    return $this->ok();
  }

  function update(
    Request $request,
    Institution $institution,
    Assessment $assessment
  ) {
    $data = $request->validate(Assessment::createRule($assessment));

    if (
      Assessment::query()
        ->forTerm($data['term'])
        ->forMidTerm($data['for_mid_term'])
        ->where('title', $data)
        ->whereNot('id', $assessment->id)
        ->exists()
    ) {
      throw ValidationException::withMessages([
        'title' => 'This title already exists'
      ]);
    }

    $assessment
      ->fill(
        collect($data)
          ->except('classification_ids')
          ->toArray()
      )
      ->save();
    $assessment->classifications()->sync($data['classification_ids'] ?? []);

    return $this->ok();
  }

  function setDependency(
    Institution $institution,
    Assessment $assessment,
    Request $request
  ) {
    $data = $request->validate([
      'depends_on' => ['nullable', new Enum(FullTermType::class)]
    ]);
    $assessment->fill($data)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, Assessment $assessment)
  {
    // $assessment->classifications()->delete();
    $assessment->delete();
    return $this->ok();
  }
}
