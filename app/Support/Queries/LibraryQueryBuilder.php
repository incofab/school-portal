<?php

namespace App\Support\Queries;

use App\Models\InstitutionUser;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class LibraryQueryBuilder extends Builder
{
    public function forStudent(Student $student): self
    {
        return $this->where('libraries.is_published', true)->where(
            fn ($query) => $query
                ->where('libraries.is_public', true)
                ->orWhereHas(
                    'classifications',
                    fn ($classificationQuery) => $classificationQuery->where(
                        'classifications.id',
                        $student->classification_id
                    )
                )
        );
    }

    public function forTeacher(InstitutionUser $institutionUser): self
    {
        return $this->where('libraries.institution_user_id', $institutionUser->id);
    }
}
