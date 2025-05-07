<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Enums\LearningEvaluationDomainType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\LearningEvaluationDomain;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class LearningEvaluationDomainController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  function index(
    Request $request,
    Institution $institution,
    ?LearningEvaluationDomain $learningEvaluationDomain = null
  ) {
    return Inertia::render(
      'institutions/learning-evaluations/list-learning-evaluation-domains',
      [
        'learningEvaluationDomains' => $institution
          ->learningEvaluationDomains()
          ->get(),
        'learningEvaluationDomain' => $learningEvaluationDomain
      ]
    );
  }

  function store(
    Request $request,
    Institution $institution,
    ?LearningEvaluationDomain $learningEvaluationDomain = null
  ) {
    $data = $request->validate([
      'title' => [
        'required',
        Rule::unique('learning_evaluation_domains', 'title')
          ->where('institution_id', $institution->id)
          ->ignore($learningEvaluationDomain?->id, 'id')
      ],
      'type' => ['required', new Enum(LearningEvaluationDomainType::class)],
      'max' => [
        'nullable',
        'integer',
        'min:1',
        'max:100',
        Rule::requiredIf(
          $request->type === LearningEvaluationDomainType::Number->value
        )
      ]
    ]);

    $learningEvaluationDomain
      ? $learningEvaluationDomain->fill($data)->save()
      : $institution->learningEvaluationDomains()->create($data);

    return $this->ok();
  }

  function destroy(
    Institution $institution,
    LearningEvaluationDomain $learningEvaluationDomain
  ) {
    $learningEvaluationDomain->delete();
    return $this->ok();
  }
}
