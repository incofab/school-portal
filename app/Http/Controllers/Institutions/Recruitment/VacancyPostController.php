<?php

namespace App\Http\Controllers\Institutions\Recruitment;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\RecruitmentApplication;
use App\Models\VacancyPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VacancyPostController extends Controller
{
    public function __construct()
    {
        $this->allowedRoles([InstitutionUserType::Admin])->except([
            'publicIndex',
            'publicShow',
        ]);
    }

    public function index(Institution $institution)
    {
        return Inertia::render('institutions/recruitment/list-vacancy-posts', [
            'vacancyPosts' => paginateFromRequest(
                $institution
                    ->vacancyPosts()
                    ->withCount('recruitmentApplications')
                    ->latest()
            ),
        ]);
    }

    public function create(Institution $institution)
    {
        return Inertia::render('institutions/recruitment/create-edit-vacancy-post');
    }

    public function store(Request $request, Institution $institution)
    {
        $data = $request->validate(VacancyPost::createRule());
        $vacancyPost = $institution->vacancyPosts()->create($data);

        return $this->ok(['vacancyPost' => $vacancyPost]);
    }

    public function edit(Institution $institution, VacancyPost $vacancyPost)
    {
        abort_unless($vacancyPost->institution_id === $institution->id, 404);

        return Inertia::render('institutions/recruitment/create-edit-vacancy-post', [
            'vacancyPost' => $vacancyPost,
        ]);
    }

    public function update(Request $request, Institution $institution, VacancyPost $vacancyPost)
    {
        abort_unless($vacancyPost->institution_id === $institution->id, 404);

        $data = $request->validate(VacancyPost::createRule());
        $vacancyPost->update($data);

        return $this->ok(['vacancyPost' => $vacancyPost]);
    }

    public function destroy(Institution $institution, VacancyPost $vacancyPost)
    {
        abort_unless($vacancyPost->institution_id === $institution->id, 404);

        if (
            RecruitmentApplication::where('vacancy_post_id', $vacancyPost->id)->exists()
        ) {
            $vacancyPost->delete();
        } else {
            $vacancyPost->forceDelete();
        }

        return $this->ok();
    }

    public function publicIndex(Institution $institution)
    {
        return Inertia::render('institutions/recruitment/public-vacancy-posts', [
            'institution' => $institution,
            'vacancyPosts' => $institution
                ->vacancyPosts()
                ->isPublished()
                ->latest()
                ->get(),
        ]);
    }

    public function publicShow(Institution $institution, VacancyPost $vacancyPost)
    {
        abort_unless(
            $vacancyPost->institution_id === $institution->id && $vacancyPost->is_published,
            404
        );

        return Inertia::render('institutions/recruitment/public-vacancy-post', [
            'institution' => $institution,
            'vacancyPost' => $vacancyPost,
        ]);
    }
}
