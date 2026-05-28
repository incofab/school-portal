<?php

namespace App\Actions\Recruitment;

use App\Models\Institution;
use App\Models\RecruitmentApplication;
use App\Models\VacancyPost;

class RecordRecruitmentApplication
{
    public function __construct(private Institution $institution) {}

    public function run(VacancyPost $vacancyPost, array $data): RecruitmentApplication
    {
        return $this->institution->recruitmentApplications()->create([
            ...collect($data)
                ->except('vacancy_post_id')
                ->toArray(),
            'vacancy_post_id' => $vacancyPost->id,
            'application_no' => RecruitmentApplication::generateApplicationNo(),
        ]);
    }
}
