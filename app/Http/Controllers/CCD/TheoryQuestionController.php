<?php

namespace App\Http\Controllers\CCD;

use App\Http\Controllers\Controller;
use App\Http\Requests\TheoryQuestionRequest;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\TheoryQuestion;

class TheoryQuestionController extends Controller
{
    public function index(Institution $institution, CourseSession $courseSession)
    {
        $this->authorizeCourseSession($institution, $courseSession);

        return view('ccd/theory-questions/index', [
            'allRecords' => paginateFromRequest(
                $courseSession
                    ->theoryQuestions()
                    ->oldest('question_number')
                    ->oldest('question_sub_number')
            ),
            'courseSession' => $courseSession->load('course'),
        ]);
    }

    public function create(Institution $institution, CourseSession $courseSession)
    {
        $this->authorizeCourseSession($institution, $courseSession);

        $lastQuestion = $courseSession
            ->theoryQuestions()
            ->latest('question_number')
            ->first();
        $questionNumber = intval($lastQuestion?->question_number) + 1;

        return view('ccd/theory-questions/create-theory-question', [
            'edit' => null,
            'questionNumber' => $questionNumber,
            'courseSession' => $courseSession->load('course'),
        ]);
    }

    public function store(
        Institution $institution,
        CourseSession $courseSession,
        TheoryQuestionRequest $request
    ) {
        $this->authorizeCourseSession($institution, $courseSession);

        $data = $request->validated();
        $data['institution_id'] = $institution->id;

        $courseSession->theoryQuestions()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'question_number' => $data['question_number'],
                'question_sub_number' => $data['question_sub_number'] ?? null,
            ],
            $data
        );

        return $this->res(
            successRes('Theory question created'),
            instRoute('theory-questions.create', [$courseSession])
        );
    }

    public function edit(Institution $institution, TheoryQuestion $theoryQuestion)
    {
        $this->authorizeTheoryQuestion($institution, $theoryQuestion);

        return view('ccd/theory-questions/create-theory-question', [
            'edit' => $theoryQuestion,
            'courseSession' => $theoryQuestion->courseSession->load('course'),
            'questionNumber' => $theoryQuestion->question_number,
        ]);
    }

    public function update(
        Institution $institution,
        TheoryQuestion $theoryQuestion,
        TheoryQuestionRequest $request
    ) {
        $this->authorizeTheoryQuestion($institution, $theoryQuestion);

        $theoryQuestion->fill($request->validated())->save();

        return $this->res(
            successRes('Theory question record updated'),
            instRoute('theory-questions.index', [$theoryQuestion->course_session_id])
        );
    }

    public function destroy(Institution $institution, TheoryQuestion $theoryQuestion)
    {
        $this->authorizeTheoryQuestion($institution, $theoryQuestion);

        $institutionUser = currentInstitutionUser();
        abort_unless($institutionUser->isAdmin(), 403, 'Access denied');

        $courseSessionId = $theoryQuestion->course_session_id;
        $theoryQuestion->delete();

        return $this->res(
            successRes('Theory question record deleted'),
            instRoute('theory-questions.index', [$courseSessionId])
        );
    }

    private function authorizeCourseSession(
        Institution $institution,
        CourseSession $courseSession
    ): void {
        abort_unless($courseSession->institution_id === $institution->id, 404);
    }

    private function authorizeTheoryQuestion(
        Institution $institution,
        TheoryQuestion $theoryQuestion
    ): void {
        abort_unless($theoryQuestion->institution_id === $institution->id, 404);
    }
}
