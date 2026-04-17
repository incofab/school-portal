<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class SchemeOfWorkUITableFilters extends BaseUITableFilter
{
    protected array $sortableColumns = [
        'weekNumber' => 'week_number',
        'updatedAt' => 'updated_at',
        'createdAt' => 'created_at',
    ];

    protected function extraValidationRules(): array
    {
        return [
            'classificationGroup' => ['sometimes', 'integer'],
            'classification' => ['sometimes', 'integer'],
            'course' => ['sometimes', 'integer'],
            'term' => ['sometimes', new Enum(TermType::class)],
        ];
    }

    protected function generalSearch(string $search)
    {
        $this->baseQuery->where(
            fn ($q) => $q
                ->where('scheme_of_works.learning_objectives', 'like', "%$search%")
                ->orWhere('scheme_of_works.resources', 'like', "%$search%")
                ->orWhereHas(
                    'topic',
                    fn ($topicQuery) => $topicQuery->where('title', 'like', "%$search%")
                )
                ->orWhereHas(
                    'topic.course',
                    fn ($courseQuery) => $courseQuery->where('title', 'like', "%$search%")
                )
                ->orWhereHas(
                    'topic.classificationGroup',
                    fn ($classGroupQuery) => $classGroupQuery->where(
                        'title',
                        'like',
                        "%$search%"
                    )
                )
        );
    }

    protected function directQuery()
    {
        $this->baseQuery
            ->when(
                $this->requestGet('classificationGroup'),
                fn ($q, $value) => $q->whereHas(
                    'topic',
                    fn ($topicQuery) => $topicQuery->where(
                        'classification_group_id',
                        $value
                    )
                )
            )
            ->when(
                $this->requestGet('classification'),
                fn ($q, $value) => $q->whereHas(
                    'topic.classificationGroup.classifications',
                    fn ($classificationQuery) => $classificationQuery->where(
                        'classifications.id',
                        $value
                    )
                )
            )
            ->when(
                $this->requestGet('course'),
                fn ($q, $value) => $q->whereHas(
                    'topic',
                    fn ($topicQuery) => $topicQuery->where('course_id', $value)
                )
            )
            ->when(
                $this->getTerm(),
                fn ($q, $value) => $q->where('scheme_of_works.term', $value)
            );

        return $this;
    }
}
