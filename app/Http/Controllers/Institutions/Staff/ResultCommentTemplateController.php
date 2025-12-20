<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Enums\ResultCommentTemplateType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class ResultCommentTemplateController extends Controller
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
    ?ResultCommentTemplate $resultCommentTemplate = null
  ) {
    $resultCommentTemplate?->load('classifications');
    return Inertia::render(
      'institutions/result-comments/list-result-comment-templates',
      [
        'resultCommentTemplates' => $institution
          ->resultCommentTemplates()
          ->latest('type')
          ->latest('min')
          ->with('classifications')
          ->get(),
        'resultCommentTemplate' => $resultCommentTemplate,
        'classifications' => $institution->classifications()->get()
      ]
    );
  }

  function store(
    Request $request,
    Institution $institution,
    ?ResultCommentTemplate $resultCommentTemplate = null
  ) {
    $data = $request->validate([
      'comment' => ['nullable', 'string'],
      'comment_2' => ['nullable', 'string'],
      'grade' => ['nullable', 'string'],
      'grade_label' => ['nullable', 'string'],
      'min' => ['required', 'numeric'],
      'max' => ['required', 'numeric', 'gte:min'],
      'type' => ['nullable', new Enum(ResultCommentTemplateType::class)],
      'classification_ids.*' => [
        'integer',
        new ValidateExistsRule(Classification::class)
      ]
    ]);

    abort_if(
      empty($request->comment) &&
        empty($request->grade) &&
        empty($request->grade_label),
      403,
      'Either of grade or comment must be supplied'
    );

    $conflictingTemplates = $institution
      ->resultCommentTemplates()
      ->when(
        $resultCommentTemplate,
        fn($q) => $q->where('id', '!=', $resultCommentTemplate->id)
      )
      ->when(
        $request->type,
        fn($q) => $q->where('type', $request->type),
        fn($q) => $q->whereNull('type')
      )
      ->where(function ($query) use ($request) {
        $query
          ->where(
            fn($q) => $q
              ->where('min', '<=', $request->min)
              ->where('max', '>=', $request->min)
          )
          ->orWhere(
            fn($q) => $q
              ->where('min', '<=', $request->max)
              ->where('max', '>=', $request->max)
          );
      })
      ->with('classifications')
      ->get();

    foreach ($conflictingTemplates as $key => $conflictingTemplate) {
      if ($conflictingTemplate->id === $resultCommentTemplate?->id) {
        continue;
      }
      abort_if(
        $conflictingTemplate->classifications->isEmpty() ||
          empty($data['classification_ids']),
        403,
        "There's a conflict in the min and max values"
      );
      $conflictingClassificationIds = $conflictingTemplate->classifications
        ->pluck('id')
        ->toArray();
      abort_if(
        array_intersect(
          $conflictingClassificationIds,
          $data['classification_ids'] ?? []
        ),
        403,
        "There's a conflict in the min and max values"
      );
    }

    $filteredData = collect($data)
      ->except(['classification_ids'])
      ->toArray();
    if ($resultCommentTemplate) {
      $resultCommentTemplate->fill($filteredData)->save();
    } else {
      $resultCommentTemplate = $institution
        ->resultCommentTemplates()
        ->create($filteredData);
    }

    $resultCommentTemplate
      ->classifications()
      ->sync($data['classification_ids'] ?? []);

    return $this->ok();
  }

  function destroy(
    Institution $institution,
    ResultCommentTemplate $resultCommentTemplate
  ) {
    $resultCommentTemplate->delete();
    return $this->ok();
  }
}
