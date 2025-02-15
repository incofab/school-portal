<?php

namespace App\Http\Controllers\Institutions\Notes;

use Inertia\Inertia;
use App\Models\NoteTopic;
use App\Models\Institution;
use App\Models\NoteSubTopic;
use Illuminate\Http\Request;
use App\Enums\NoteStatusType;
use App\Models\CourseTeacher;
use App\Models\Classification;
use App\Support\SettingsHandler;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;

class NoteSubTopicController extends Controller
{
    //
    public function __construct()
    {
        $this->allowedRoles([
            InstitutionUserType::Admin,
            InstitutionUserType::Teacher
        ])->except('list', 'show');
    }

    function create(Institution $institution, NoteTopic $noteTopic)
    {
        //== Check if the teacher teaches this Subject & has permission to create notes for the subject.
        $institutionUser = currentInstitutionUser();

        if ($institutionUser->isTeacher()) {
            if ($institutionUser->user->id != $noteTopic->courseTeacher->user_id) {
                abort(403, "Only a Note's Creator is allowed to edit the Note.");
            }
        }

        return Inertia::render('institutions/notes/create-edit-note-subtopic', [
            'noteTopic' => $noteTopic->load('course', 'classification'),
        ]);
    }

    function edit(Institution $institution, NoteSubTopic $noteSubTopic)
    {
        $institutionUser = currentInstitutionUser();

        if ($institutionUser->isTeacher()) {
            /** == CHECK IF THE TEACHER IS PERMITTED TO EDIT THIS NOTE-SUB-TOPIC == */
            if ($institutionUser->user->id != $noteSubTopic->noteTopic->courseTeacher->user_id) {
                abort(403, "Only a Note's Creator is allowed to edit the Note.");
            }
        }

        return Inertia::render('institutions/notes/create-edit-note-subtopic', [
            'noteSubTopic' => $noteSubTopic->with('noteTopic.course', 'noteTopic.classification')->first(),
        ]);
    }

    public function list(Institution $institution, NoteTopic $noteTopic)
    {
        $institutionUser = currentInstitutionUser();
        $user = $institutionUser->user;

        $settingHandler = SettingsHandler::makeFromRoute();
        $currentTerm = $settingHandler->getCurrentTerm();

        if ($institutionUser->isStudent()) {
            $student = $institutionUser?->student()->with('classification')->first();

            $noteTopicIds = NoteTopic::where("classification_id", $student->classification_id)
                ->where('term', $currentTerm)
                ->with('course')
                ->with('classification')
                ->pluck('id');
        } else if ($institutionUser->isTeacher()) {

            /* == With the code below, all Teachers that teaches a particular subject for a particular class will have access to the NoteTopic == */
            $teacherCourses = CourseTeacher::where('user_id', $user->id)
                ->get(['course_id', 'classification_id'])
                ->map(function ($item) {
                    return [$item->course_id, $item->classification_id];
                });

            $teacherCourseIds = $teacherCourses->pluck(0);
            $teacherClassIds = $teacherCourses->pluck(1);

            $noteTopicIds = $noteTopic->with('course')
                ->with('classification')
                ->whereIn("course_id", $teacherCourseIds)
                ->whereIn("classification_id", $teacherClassIds)
                ->pluck('id');

            /* == With the code below, only ONE Teacher (One Course Teacher) (and the Admin) can view a NoteTopic */
            /*
                $courseTeacherIds = CourseTeacher::where('user_id', $user->id)->pluck('id');
                $noteTopicIds = NoteTopic::whereIn("course_teacher_id", $courseTeacherIds)->with('course')->with('classification')->pluck('id');
            */
        } else if ($institutionUser->isAdmin()) {
            $noteTopicIds = NoteTopic::with('course')->with('classification')->pluck('id');
        } else {
            abort(401, "Unauthorized");
        }

        $noteSubTopics = NoteSubTopic::whereIn('note_topic_id', $noteTopicIds);

        return Inertia::render('institutions/notes/list-note-subtopics', [
            'noteTopic' => $noteTopic,
            'noteSubTopics' => paginateFromRequest($noteSubTopics->latest('id')->with('noteTopic')),
        ]);
    }

    function store(Institution $institution, NoteTopic $noteTopic, Request $request)
    {
        $data = $request->validate(NoteSubTopic::createRule());

        // == Create Record.
        NoteSubTopic::create([
            'note_topic_id' => $noteTopic->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $request->is_published ? NoteStatusType::Published : NoteStatusType::Draft,
        ]);

        return $this->ok();
    }

    function update(Institution $institution, NoteSubTopic $noteSubTopic, Request $request)
    {
        $user = currentUser();
        $data = $request->validate(NoteSubTopic::createRule());

        // == Grab the Teacher assigned to the Course #NoteTopic
        $courseTeacherId = $noteSubTopic->noteTopic->course_teacher_id;
        $courseTeacher = CourseTeacher::find($courseTeacherId);

        if (!$courseTeacher) {
            abort(401, 'Course Teacher not found.');
        }

        // == Check if currentUser is an admin or the specific course teacher assigned to this noteTopic
        // ** Hence only the COURSE TEACHER (or ADMIN) can update the NoteTopic.
        if (!$user->isInstitutionAdmin() && $courseTeacher->user_id !== $user->id) {
            abort(403, 'Forbidden');
        }

        $noteSubTopic->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $request->is_published ? NoteStatusType::Published : NoteStatusType::Draft,
        ]);

        return $this->ok();
    }

    function show(Institution $institution, NoteSubTopic $noteSubTopic)
    {
        $institutionUser = currentInstitutionUser();

        if ($institutionUser->isStudent()) {
            $currentTerm = SettingsHandler::makeFromRoute()->getCurrentTerm();
            $student = currentInstitutionUser()->student()->with('classification')->first();

            if ($noteSubTopic->noteTopic->classification_id != $student->classification_id) {
                abort(403, "You are not eligible for this Note.");
            }

            if ($noteSubTopic->noteTopic->term != $currentTerm) {
                abort(403, "This Note is not for this Term.");
            }
        }

        return Inertia::render('institutions/notes/show-note', [
            'note' => $noteSubTopic,
        ]);
    }

    function destroy(Institution $institution, NoteSubTopic $noteSubTopic)
    {
        $institutionUser = currentInstitutionUser();

        if ($institutionUser->isTeacher()) {
            if ($institutionUser->user->id != $noteSubTopic->noteTopic->courseTeacher->user_id) {
                return $this->message("Only a Note's Creator is allowed to delete the Note.", 403);
            };
        }

        $noteSubTopic->delete();
        return $this->ok();
    }
}