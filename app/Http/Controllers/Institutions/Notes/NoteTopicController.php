<?php

namespace App\Http\Controllers\Institutions\Notes;

use Inertia\Inertia;
use App\Models\NoteTopic;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\CourseTeacher;
use App\Support\SettingsHandler;
use App\Enums\InstitutionUserType;
use App\Enums\NoteStatusType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\InstitutionUser;
use App\Support\UITableFilters\NoteTopicUITableFilters;

class NoteTopicController extends Controller
{
    //
    public function __construct()
    {
        $this->allowedRoles([
            InstitutionUserType::Admin,
            InstitutionUserType::Teacher
        ])->except('index', 'show');
    }

    //== Listing
    public function index(Request $request)
    {
        $institutionUser = currentInstitutionUser();
        $user = $institutionUser->user;

        /*
        $settingHandler = SettingsHandler::makeFromRoute();
        $currentTerm = $settingHandler->getCurrentTerm();
        if ($institutionUser->isStudent()) {
            $student = $institutionUser?->student()->with('classification')->first();

            $noteTopics = NoteTopic::where("classification_id", $student->classification_id)
                ->where('term', $currentTerm)
                ->with('course')
                ->with('classification');
        } else if ($institutionUser->isTeacher()) {

            $teacherCourses = CourseTeacher::where('user_id', $user->id)
                ->get(['course_id', 'classification_id'])
                ->map(function ($item) {
                    return [$item->course_id, $item->classification_id];
                });

            $teacherCourseIds = $teacherCourses->pluck(0);
            $teacherClassIds = $teacherCourses->pluck(1);

            $noteTopics = NoteTopic::whereIn("course_id", $teacherCourseIds)
                ->whereIn("classification_id", $teacherClassIds)
                ->with('course')
                ->with('classification');
        } else if ($institutionUser->isAdmin()) {
            $noteTopics = NoteTopic::with('course')->with('classification');
        } else {
            abort(401, "Unauthorized");
        }
        */

        $query = NoteTopic::query();
        $requestData = $request->all();
        if ($institutionUser->isStudent()) {
            $student = $institutionUser->student()->with('classification')->first();
            $requestData['classification_id'] = $student->classification_id;
            $requestData['classification_group_id'] = $student->classification_group_id;
        } else if ($institutionUser->isTeacher()) {

            $teacherCourses = CourseTeacher::where('user_id', $user->id)
                ->get(['course_id', 'classification_id'])
                ->map(function ($item) {
                    return [$item->course_id, $item->classification_id];
                });

            $teacherCourseIds = $teacherCourses->pluck(0);
            $teacherClassIds = $teacherCourses->pluck(1);
            $requestData['classification_id'] = null;
            $requestData['classification_group_id'] = null;
            $requestData['course_id'] = null;

            $query->whereIn("course_id", $teacherCourseIds)
                ->whereIn("classification_id", $teacherClassIds);
        }

        NoteTopicUITableFilters::make($requestData, $query)->filterQuery();

        return Inertia::render('institutions/notes/list-note-topics', [
            'noteTopics' => paginateFromRequest($query->with('classification', 'course')->latest('id')),
            'classificationGroups' => ClassificationGroup::all()
        ]);
    }

    function create()
    {
        $institutionUser = currentInstitutionUser();
        $teacherCourses = [];

        if ($institutionUser->isTeacher()) {
            $teacherCourses = CourseTeacher::where('user_id', $institutionUser->user->id)->with('course', 'classification')->get();
        }

        return Inertia::render('institutions/notes/create-edit-note-topic', [
            'teacherCourses' => $teacherCourses,
        ]);
    }

    function edit(Institution $institution, NoteTopic $noteTopic)
    {
        $institutionUser = currentInstitutionUser();
        $teacherCourses = [];

        if ($institutionUser->isTeacher()) {
            /** == CHECK IF THE TEACHER IS PERMITTED TO EDIT THIS NOTE-TOPIC == */

            /* == With the code below, only ONE Teacher (The Course Teacher assigned to this Note) can edit it */
            if ($institutionUser->user->id != $noteTopic->courseTeacher->user_id) {
                abort(403, "Only a Note's Creator is allowed to edit the Note.");
            }

            /* == With the code below, all Teachers that teaches a particular subject for a particular class will be able to edit the NoteTopic == */
            /*
            $teacherCourse = CourseTeacher::where('user_id', $institutionUser->user->id)
                ->where('course_id', $noteTopic->course_id)
                ->where('classification_id', $noteTopic->classification_id)
                ->first();

            if (!$teacherCourse) {
                abort(403, "You are not permitted to edit this Note");
            }
            */


            /** == Return $teacherCourses incase the Teacher wants to change the Course/Class of the Note == */
            $teacherCourses = CourseTeacher::where('user_id', $institutionUser->user->id)->with('course', 'classification')->get();
        }

        return Inertia::render('institutions/notes/create-edit-note-topic', [
            'noteTopic' => $noteTopic->load('courseTeacher.user', 'course', 'classification'),
            'teacherCourses' => $teacherCourses,
        ]);
    }

    function show(Institution $institution, NoteTopic $noteTopic)
    {
        $institutionUser = currentInstitutionUser();

        if ($institutionUser->isStudent()) {
            $currentTerm = SettingsHandler::makeFromRoute()->getCurrentTerm();
            $student = currentInstitutionUser()->student()->with('classification')->first();

            if ($noteTopic->classification_id != $student->classification_id) {
                abort(403, "You are not eligible for this Note.");
            }

            if ($noteTopic->term != $currentTerm) {
                abort(403, "This Note is not for this Term.");
            }
        }

        return Inertia::render('institutions/notes/show-note', [
            'note' => $noteTopic,
        ]);
    }

    function store(Institution $institution, Request $request)
    {
        $data = $request->validate(NoteTopic::createRule());

        // == Grab 'courseId' and 'classificationId'
        $courseTeacher = CourseTeacher::find($data['course_teacher_id']);

        if (!$courseTeacher) {
            abort('Course Teacher not found.', 401);
        }

        $classification = Classification::find($courseTeacher->classification_id);
        $classificationGroup = $classification->classificationGroup;

        // == Create Record.
        NoteTopic::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $request->is_published ? NoteStatusType::Published : NoteStatusType::Draft,
            'institution_group_id' => $institution->institutionGroup->id,
            'institution_id' => $institution->id,
            'course_teacher_id' => $data['course_teacher_id'],
            'classification_group_id' => $classificationGroup->id,
            'classification_id' => $courseTeacher->classification_id,
            'course_id' => $courseTeacher->course_id,
            'term' => SettingsHandler::makeFromRoute()->getCurrentTerm(),
        ]);

        return $this->ok();
    }

    function update(Request $request, Institution $institution, NoteTopic $noteTopic)
    {
        $user = currentUser();
        $data = $request->validate(NoteTopic::createRule());

        // == Grab 'courseId' and 'classificationId'
        $courseTeacher = CourseTeacher::find($data['course_teacher_id']);

        if (!$courseTeacher) {
            abort(401, 'Course Teacher not found.');
        }

        // == Check if user is an admin or the specific course teacher assigned to this noteTopic
        // ** Hence only the COURSE TEACHER (or ADMIN) can update the NoteTopic.
        if (!$user->isInstitutionAdmin() && $courseTeacher->user_id !== $user->id) {
            abort(403, 'Forbidden');
        }

        $classification = Classification::find($courseTeacher->classification_id);
        $classificationGroup = $classification->classificationGroup;

        $noteTopic->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $request->is_published ? NoteStatusType::Published : NoteStatusType::Draft,
            'course_teacher_id' => $data['course_teacher_id'],
            'classification_group_id' => $classificationGroup->id,
            'classification_id' => $courseTeacher->classification_id,
            'course_id' => $courseTeacher->course_id,
        ]);

        return $this->ok();
    }

    function destroy(Institution $institution, NoteTopic $noteTopic)
    {
        if ($noteTopic->subTopics()->exists()) {
            return $this->message("This Note Topic already has some Sub-Topic Notes.", 403);
        }

        $institutionUser = currentInstitutionUser();

        if ($institutionUser->isTeacher()) {
            if ($institutionUser->user->id != $noteTopic->courseTeacher->user_id) {
                return $this->message("Only a Note's Creator is allowed to delete the Note.", 403);
            };
        }

        $noteTopic->delete();
        return $this->ok();
    }
}