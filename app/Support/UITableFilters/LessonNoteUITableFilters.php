<?php

namespace App\Support\UITableFilters;

use App\Enums\NoteStatusType;
use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class LessonNoteUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'title' => 'title',
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'courseTeacher' => ['sometimes', 'integer'],
      'classificationGroup' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'course' => ['sometimes', 'integer'],
      'weekNumber' => ['sometimes', 'string'],
      // 'title' => ['sometimes', 'string'],
      'term' => ['sometimes', new Enum(TermType::class)],
      'status' => ['sometimes', new Enum(NoteStatusType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q->where('lesson_notes.title', 'like', "%$search%")
    );
  }

  function joinLessonPlan()
  {
    return $this->callOnce(
      'joinLessonPlan',
      fn() => $this->baseQuery->join(
        'lesson_plans',
        'lesson_plans.id',
        'lesson_notes.lesson_plan_id'
      )
    );
  }

  function joinSchemeOfWork()
  {
    return $this->joinLessonPlan()->callOnce(
      'joinSchemeOfWork',
      fn() => $this->baseQuery->join(
        'scheme_of_works',
        'scheme_of_works.id',
        'lesson_plans.scheme_of_work_id'
      )
    );
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('courseTeacher'),
        fn($q, $value) => $q->where('lesson_notes.course_teacher_id', $value)
      )
      ->when(
        $this->requestGet('classificationGroup'),
        fn($q, $value) => $q->where(
          'lesson_notes.classification_group_id',
          $value
        )
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where('lesson_notes.classification_id', $value)
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->where('lesson_notes.course_id', $value)
      )
      ->when(
        $this->getTerm(),
        fn($q, $value) => $q->where('lesson_notes.term', $value)
      )
      ->when(
        $this->requestGet('status'),
        fn($q, $value) => $q->where('lesson_notes.status', $value)
      )
      ->when(
        $this->requestGet('weekNumber'),
        fn($q, $value) => $this->joinSchemeOfWork()
          ->getQuery()
          ->where('scheme_of_works.week_number', $value)
      );

    return $this;
  }
}
