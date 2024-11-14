<?php

namespace App\Http\Controllers\Institutions\Assignments;

use Inertia\Inertia;
use App\Models\Assignment;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\CourseTeacher;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;

class AssignmentSubmissionController extends Controller
{
    //
    public function __construct()
    {
        $this->allowedRoles([
            InstitutionUserType::Admin,
            InstitutionUserType::Teacher
        ])->except('index', 'show', 'store');
    }

    function index(Request $request, Institution $institution)
    {
        $user = currentInstitutionUser();

        if ($user->isStudent()) {
            $student = $user->student;

            $assignmentSubmissions = AssignmentSubmission::where("student_id", $student->id)->with(['assignment.course', 'assignment.classification'])->with('student.user');
        } else {
            abort(401, "Unauthorized");
        }

        /** == Removed Because we decided that Teachers and Admins should only access the 'List' route - not this 'Index' route
         * 
         *  else if ($user->isTeacher()) {
         *  $teacherCourses = CourseTeacher::where('user_id', $user->user->id)->pluck('course_id');
         *  $teacherAssignments = Assignment::whereIn('course_id', $teacherCourses)->pluck('id');
         *
         *  $assignmentSubmissions = AssignmentSubmission::whereIn("assignment_id", $teacherAssignments)->with(['assignment.course', 'assignment.classification'])->with('student.user');
         *  } else if ($user->isAdmin()) {
         *    $assignmentSubmissions = AssignmentSubmission::with(['assignment.course', 'assignment.classification'])->with('student.user');
         *  }
         */

        return Inertia::render('institutions/assignments/list-assignment-submissions', [
            'assignmentSubmissions' => paginateFromRequest($assignmentSubmissions->latest('id')),
        ]);
    }

    /**
     * This function shows a list of all assignmentSubmissions for a particular/given Assignment
     */
    function list(Institution $institution, Assignment $assignment)
    {
        $user = currentUser();
        $institutionUser = currentInstitutionUser();

        if ($institutionUser->isTeacher()) {
            // $teacherCourses = CourseTeacher::where('user_id', $user->user->id)->pluck('course_id');
            // $teacherAssignments = Assignment::whereIn('course_id', $teacherCourses)->pluck('id');

            // if (!$teacherAssignments->contains($assignment->id)) {
            //     abort(401, "Unauthorized Operation");
            // }

            $assgnmentCourseTeacher = $assignment->courseTeacher()->where('user_id', $user->id)->first();

            //$assgnmentCourseTeacher = CourseTeacher::where('id', $assignment->course_teacher_id)->where('user_id', $user->id)->first();

            // $teacherAssignments = Assignment::select('assignments.*')
            //     ->join('course_teachers', 'course_teachers.id', 'assignments.course_teacher_id')
            //     ->where('course_teachers.user_id', $user->id)
            //     ->where('assignments.id', $assignment->id)
            //     ->first();

            abort_unless($assgnmentCourseTeacher, 401, "Unauthorized Operation");
        }

        $assignmentSubmissions = AssignmentSubmission::where('assignment_id', $assignment->id)->with(['assignment.course', 'assignment.classification'])->with('student.user');

        return Inertia::render('institutions/assignments/list-assignment-submissions', [
            'assignmentSubmissions' => paginateFromRequest($assignmentSubmissions->latest('id')),
        ]);
    }

    function show(Institution $institution, AssignmentSubmission $assignmentSubmission)
    {
        $user = currentInstitutionUser();

        if ($user->isStudent()) {
            $student = currentInstitutionUser()->student;

            if ($assignmentSubmission->student_id != $student->id) {
                abort(401, "Unauthorized Operation.");
            }
        } else if ($user->isTeacher()) {
            $assignment_course_teacher_user_id = $assignmentSubmission->assignment->courseTeacher->user_id;
            $current_user_id = $user->user->id;

            if ($assignment_course_teacher_user_id != $current_user_id) {
                abort(401, "Unauthorized Operation.");
            }
        } else if ($user->isAdmin()) {
        } else {
            abort(401, "Unauthorized");
        }

        return Inertia::render('institutions/assignments/show-assignment-submission', [
            'assignmentSubmission' => $assignmentSubmission->load('assignment'),
        ]);
    }

    function store(Institution $institution, Request $request)
    {
        $user = currentInstitutionUser();
        if (!$user->isStudent()) {
            abort(401, 'Unauthorized Operation');
        };

        $data = $request->validate([
            'assignment_id' => 'required|integer',
            'answer' => 'required|string',
        ]);

        $student = $user->student;

        AssignmentSubmission::create([
            ...$data,
            'student_id' => $student->id
        ]);
        return $this->ok();
    }

    function score(Institution $institution, AssignmentSubmission $assignmentSubmission, Request $request)
    {
        $user = currentInstitutionUser();

        $assignment_course_teacher_user_id = $assignmentSubmission->assignment->courseTeacher->user_id;
        $current_user_id = $user->user->id;

        if (!$user->isAdmin() && ($assignment_course_teacher_user_id != $current_user_id)) {
            abort(403, 'Unauthorized');
        };

        $maxScore = $assignmentSubmission->assignment->max_score;

        $request->validate([
            'score' => 'required|integer|min:0|max:' . $maxScore,
            'remark' => 'nullable|string',
        ]);

        $assignmentSubmission->update([
            'score' => $request->score,
            'remark' => $request->remark,
        ]);

        return $this->ok();
    }
}