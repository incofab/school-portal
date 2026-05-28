<?php

namespace App\Support\UITableFilters;

use App\Enums\LibrarySourceType;
use Illuminate\Validation\Rules\Enum;

class LibraryUITableFilters extends BaseUITableFilter
{
    protected array $sortableColumns = [
        'title' => 'title',
        'publishedAt' => 'published_at',
        'createdAt' => 'created_at',
    ];

    protected function extraValidationRules(): array
    {
        return [
            'classification' => ['sometimes', 'integer'],
            'material_type' => ['sometimes', 'string'],
            'course' => ['sometimes', 'integer'],
            'source_type' => ['sometimes', new Enum(LibrarySourceType::class)],
        ];
    }

    protected function generalSearch(string $search)
    {
        $this->baseQuery->where(
            fn ($query) => $query
                ->where('libraries.title', 'like', "%{$search}%")
                ->orWhere('libraries.description', 'like', "%{$search}%")
                ->orWhereHas(
                    'course',
                    fn ($courseQuery) => $courseQuery->where(
                        'courses.title',
                        'like',
                        "%{$search}%"
                    )
                )
        );

        return $this;
    }

    protected function directQuery()
    {
        $this->baseQuery
            ->when(
                $this->requestGet('classification'),
                fn ($query, $value) => $query->whereHas(
                    'classifications',
                    fn ($classificationQuery) => $classificationQuery->where(
                        'classifications.id',
                        $value
                    )
                )
            )
            ->when(
                $this->requestGet('material_type'),
                fn ($query, $value) => $query->where(
                    'libraries.material_type',
                    $value
                )
            )
            ->when(
                $this->requestGet('course'),
                fn ($query, $value) => $query->where('libraries.course_id', $value)
            )
            ->when(
                $this->requestGet('source_type'),
                fn ($query, $value) => $query->where('libraries.source_type', $value)
            );

        return $this;
    }
}
