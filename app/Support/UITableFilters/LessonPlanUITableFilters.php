<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class LessonPlanUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'updatedAt' => 'updated_at',
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'courseTeacher' => ['sometimes', 'integer'],
      'classificationGroup' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'course' => ['sometimes', 'integer'],
      'term' => ['sometimes', new Enum(TermType::class)],
      'weekNumber' => ['sometimes', 'string']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q
        ->where('lesson_plans.objective', 'like', "%$search%")
        ->orWhere('lesson_plans.activities', 'like', "%$search%")
        ->orWhere('lesson_plans.content', 'like', "%$search%")
        ->orWhereHas(
          'schemeOfWork.topic',
          fn($topicQuery) => $topicQuery->where('title', 'like', "%$search%")
        )
        ->orWhereHas(
          'schemeOfWork.topic.course',
          fn($courseQuery) => $courseQuery->where('title', 'like', "%$search%")
        )
        ->orWhereHas(
          'schemeOfWork.topic.classificationGroup',
          fn($classGroupQuery) => $classGroupQuery->where(
            'title',
            'like',
            "%$search%"
          )
        )
    );
  }

  function joinSchemeOfWork()
  {
    return $this->callOnce(
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
        fn($q, $value) => $q->where('lesson_plans.course_teacher_id', $value)
      )
      ->when(
        $this->requestGet('classificationGroup'),
        fn($q, $value) => $q->whereHas(
          'schemeOfWork.topic',
          fn($topicQuery) => $topicQuery->where(
            'classification_group_id',
            $value
          )
        )
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->whereHas(
          'courseTeacher',
          fn($courseTeacherQuery) => $courseTeacherQuery->where(
            'classification_id',
            $value
          )
        )
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->whereHas(
          'schemeOfWork.topic',
          fn($topicQuery) => $topicQuery->where('course_id', $value)
        )
      )
      ->when(
        $this->getTerm(),
        fn($q, $value) => $q->whereHas(
          'schemeOfWork',
          fn($schemeQuery) => $schemeQuery->where('term', $value)
        )
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
