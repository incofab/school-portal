<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Enums\ResultCommentTemplateType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
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
    return Inertia::render(
      'institutions/result-comments/list-result-comment-templates',
      [
        'resultCommentTemplates' => $institution
          ->resultCommentTemplates()
          ->latest('type')
          ->latest('min')
          ->get(),
        'resultCommentTemplate' => $resultCommentTemplate
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
      'type' => ['nullable', new Enum(ResultCommentTemplateType::class)]
    ]);

    abort_if(
      empty($request->comment) &&
        empty($request->grade) &&
        empty($request->grade_label),
      403,
      'Either of grade or comment must be supplied'
    );

    $conflictingTemplate = $institution
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
      ->first();

    abort_if(
      $conflictingTemplate,
      403,
      "There's a conflict in the min and max values"
    );

    $resultCommentTemplate
      ? $resultCommentTemplate->fill($data)->save()
      : $institution->resultCommentTemplates()->create($data);

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
