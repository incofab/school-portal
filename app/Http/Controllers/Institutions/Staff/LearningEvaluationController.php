<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\LearningEvaluation;
use App\Models\LearningEvaluationDomain;
use App\Models\TermResult;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LearningEvaluationController extends Controller
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
    ?LearningEvaluation $learningEvaluation = null
  ) {
    return Inertia::render(
      'institutions/learning-evaluations/list-learning-evaluations',
      [
        'learningEvaluations' => $institution
          ->learningEvaluations()
          ->with('learningEvaluationDomain')
          ->get(),
        'learningEvaluation' => $learningEvaluation,
        'learningEvaluationDomains' => LearningEvaluationDomain::query()->get()
      ]
    );
  }

  function store(
    Request $request,
    Institution $institution,
    ?LearningEvaluation $learningEvaluation = null
  ) {
    $data = $request->validate([
      'learning_evaluation_domain_id' => [
        'required',
        Rule::exists('learning_evaluation_domains', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'title' => [
        'required',
        Rule::unique('learning_evaluations', 'title')
          ->where('institution_id', $institution->id)
          ->ignore($learningEvaluation?->id, 'id')
      ]
    ]);

    $learningEvaluation
      ? $learningEvaluation->fill($data)->save()
      : $institution->learningEvaluations()->create($data);

    return $this->ok();
  }

  function setTermResultEvaluation(
    Request $request,
    Institution $institution,
    TermResult $termResult
  ) {
    $data = $request->validate([
      'evaluations' => ['required', 'array'],
      'evaluations.*.learning_evaluation_id' => [
        'required',
        Rule::exists('learning_evaluations', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'evaluations.*.value' => ['required']
    ]);

    $formatted = [];
    foreach ($data['evaluations'] as $key => $item) {
      $formatted[$item['learning_evaluation_id']] = $item['value'];
    }
    $termResult->fill(['learning_evaluation' => $formatted])->save();
    return $this->ok(['termResult' => $termResult]);
  }

  function destroy(
    Institution $institution,
    LearningEvaluation $learningEvaluation
  ) {
    $learningEvaluation->delete();
    return $this->ok();
  }
}
