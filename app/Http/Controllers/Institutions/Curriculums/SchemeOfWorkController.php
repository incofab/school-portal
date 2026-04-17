<?php

namespace App\Http\Controllers\Institutions\Curriculums;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SchemeOfWork;
use App\Models\Topic;
use App\Support\UITableFilters\SchemeOfWorkUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchemeOfWorkController extends Controller
{
    //
    public function __construct()
    {
        $this->allowedRoles([
            InstitutionUserType::Admin,
            InstitutionUserType::Teacher,
        ])->except('index');
        $this->allowedRoles([InstitutionUserType::Admin])->only('destroy');
    }

    public function index(Institution $institution, Request $request)
    {
        $institutionUser = currentInstitutionUser();
        $query = SchemeOfWork::query();

        if ($institutionUser->isTeacher()) {
            $teacherCourses = $institutionUser->user
                ->courseTeachers()
                ->with('classification')
                ->get();
            $teacherCourseIds = $teacherCourses->pluck('course_id');
            $teacherClassificationGroupIds = $teacherCourses
                ->pluck('classification.classification_group_id')
                ->filter();

            $query
                ->whereHas(
                    'topic',
                    fn ($topicQuery) => $topicQuery->whereIn(
                        'course_id',
                        $teacherCourseIds
                    )
                )
                ->whereHas(
                    'topic',
                    fn ($topicQuery) => $topicQuery->whereIn(
                        'classification_group_id',
                        $teacherClassificationGroupIds
                    )
                );
        } elseif ($institutionUser->isStudent()) {
            $student = $institutionUser->student()->first();
            $query->whereHas(
                'topic',
                fn ($topicQuery) => $topicQuery->where(
                    'classification_group_id',
                    $student?->classification_group_id
                )
            );
        }

        SchemeOfWorkUITableFilters::make($request->all(), $query)->filterQuery();

        return Inertia::render('institutions/scheme-of-works/list-scheme-of-works', [
            'schemeOfWorks' => paginateFromRequest(
                $query
                    ->with('topic.classificationGroup', 'topic.course', 'lessonPlans')
                    ->latest('id')
            ),
        ]);
    }

    public function create(Institution $institution, Topic $topic)
    {
        return Inertia::render(
            'institutions/scheme-of-works/create-edit-scheme-of-work',
            [
                'topicId' => $topic->id,
            ]
        );
    }

    public function edit(Institution $institution, SchemeOfWork $schemeOfWork)
    {
        $parentTopics = Topic::whereNull('parent_topic_id')->get();

        return Inertia::render(
            'institutions/scheme-of-works/create-edit-scheme-of-work',
            [
                'parentTopics' => $parentTopics,
                'schemeOfWork' => $schemeOfWork->load('topic'),
            ]
        );
    }

    public function store(Institution $institution, Request $request)
    {
        $data = $request->validate(SchemeOfWork::createRule());

        $params = [
            ...collect($data)
                ->except('is_used_by_institution_group')
                ->toArray(),
            'institution_id' => $institution->id,
            'institution_group_id' => $data['is_used_by_institution_group']
              ? $institution->institutionGroup->id
              : null,
        ];

        SchemeOfWork::create($params);

        return $this->ok();
    }

    public function update(
        Institution $institution,
        Request $request,
        SchemeOfWork $schemeOfWork
    ) {
        $data = $request->validate(SchemeOfWork::createRule());

        $schemeOfWork->update([
            'term' => $data['term'],
            'topic_id' => $data['topic_id'],
            'week_number' => $data['week_number'],
            'learning_objectives' => $data['learning_objectives'],
            'resources' => $data['resources'],
            'institution_id' => $institution->id,
            'institution_group_id' => $data['is_used_by_institution_group']
              ? $institution->institutionGroup->id
              : null,
        ]);

        return $this->ok();
    }

    public function destroy(Institution $institution, SchemeOfWork $schemeOfWork)
    {
        if (count($schemeOfWork->lessonPlans()->get()) > 0) {
            return $this->message(
                'This Scheme-of-Work already has a Lesson-Plan.',
                403
            );
        }

        $schemeOfWork->delete();

        return $this->ok();
    }
}
