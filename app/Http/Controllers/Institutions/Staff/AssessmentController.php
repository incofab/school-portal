<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\FullTermType;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
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
      'assessments' => $query->get(),
      'assessment' => $assessment,
      'classDivisions' => \App\Models\ClassDivision::all()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'term' => ['nullable', new Enum(TermType::class)],
      'for_mid_term' => ['nullable', 'boolean'],
      'title' => ['required'],
      'max' => ['required', 'numeric', 'min:0', 'max:100'],
      'description' => ['nullable', 'string'],
      'class_division_ids' => ['nullable', 'array']
    ]);

    $assessment = $institution->assessments()->updateOrCreate(
      collect($data)
        ->only(['term', 'for_mid_term', 'title'])
        ->toArray(),
      collect($data)
        ->except('class_division_ids')
        ->toArray()
    );

    $assessment->classDivisions()->sync($data['class_division_ids'] ?? []);

    return $this->ok();
  }

  function update(
    Request $request,
    Institution $institution,
    Assessment $assessment
  ) {
    $data = $request->validate([
      'term' => ['nullable', new Enum(TermType::class)],
      'for_mid_term' => ['nullable', 'boolean'],
      'title' => ['required'],
      'max' => ['required', 'numeric', 'min:0', 'max:100'],
      'description' => ['nullable', 'string'],
      'class_division_ids' => ['nullable', 'array']
    ]);

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
          ->except('class_division_ids')
          ->toArray()
      )
      ->save();
    $assessment->classDivisions()->sync($data['class_division_ids'] ?? []);

    return $this->ok();
  }

  function setDependency(
    Institution $institution,
    Assessment $assessment,
    Request $request
  ) {
    $data = $request->validate([
      'depends_on' => ['required', 'nullable', new Enum(FullTermType::class)]
    ]);
    $assessment->fill($data)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, Assessment $assessment)
  {
    // $assessment->classDivisions()->delete();
    $assessment->delete();
    return $this->ok();
  }
}
