<?php

namespace App\Http\Controllers\Institutions\Recruitment;

use App\Actions\Recruitment\RecordRecruitmentApplication;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecruitmentApplicationRequest;
use App\Models\Institution;
use App\Models\RecruitmentApplication;
use App\Models\VacancyPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecruitmentApplicationController extends Controller
{
    public function __construct()
    {
        $this->allowedRoles([InstitutionUserType::Admin])->except([
            'create',
            'store',
            'success',
        ]);
    }

    public function index(Institution $institution, VacancyPost $vacancyPost)
    {
        abort_unless($vacancyPost->institution_id === $institution->id, 404);

        $query = $vacancyPost->recruitmentApplications()->with('vacancyPost');

        return Inertia::render('institutions/recruitment/list-recruitment-applications', [
            'recruitmentApplications' => paginateFromRequest($query->latest()),
            'vacancyPost' => $vacancyPost,
        ]);
    }

    public function create(Institution $institution, VacancyPost $vacancyPost)
    {
        abort_unless(
            $vacancyPost->institution_id === $institution->id && $vacancyPost->is_published,
            404
        );

        return Inertia::render('institutions/recruitment/create-recruitment-application', [
            'institution' => $institution,
            'vacancyPost' => $vacancyPost,
        ]);
    }

    public function store(
        Institution $institution,
        VacancyPost $vacancyPost,
        RecruitmentApplicationRequest $request
    ) {
        abort_unless(
            $vacancyPost->institution_id === $institution->id &&
              $request->getVacancyPost()->id === $vacancyPost->id,
            404
        );

        $recruitmentApplication = (new RecordRecruitmentApplication($institution))->run(
            $request->getVacancyPost(),
            $request->validated()
        );

        return $this->ok(['recruitmentApplication' => $recruitmentApplication]);
    }

    public function show(
        Institution $institution,
        RecruitmentApplication $recruitmentApplication
    ) {
        abort_unless($recruitmentApplication->institution_id === $institution->id, 404);

        $recruitmentApplication->load('vacancyPost');

        return Inertia::render('institutions/recruitment/show-recruitment-application', [
            'recruitmentApplication' => $recruitmentApplication,
        ]);
    }

    public function updateStatus(
        Institution $institution,
        RecruitmentApplication $recruitmentApplication,
        Request $request
    ) {
        abort_unless($recruitmentApplication->institution_id === $institution->id, 404);

        $data = $request->validate(RecruitmentApplication::statusRule());
        $recruitmentApplication->update($data);

        return $this->ok();
    }

    public function destroy(
        Institution $institution,
        RecruitmentApplication $recruitmentApplication
    ) {
        abort_unless($recruitmentApplication->institution_id === $institution->id, 404);

        $recruitmentApplication->delete();

        return $this->ok();
    }

    public function success(
        Institution $institution,
        RecruitmentApplication $recruitmentApplication
    ) {
        abort_unless($recruitmentApplication->institution_id === $institution->id, 404);

        $recruitmentApplication->load('vacancyPost');

        return Inertia::render('institutions/recruitment/recruitment-application-success', [
            'institution' => $institution,
            'recruitmentApplication' => $recruitmentApplication,
        ]);
    }
}
