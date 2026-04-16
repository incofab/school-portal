<?php

namespace App\Http\Controllers\CCD;

use App\Http\Controllers\Controller;
use App\Http\Requests\TheoryQuestionRequest;
use App\Models\CourseSession;
use App\Models\EventCourseable;
use App\Models\Institution;
use App\Models\Support\QuestionCourseable;
use App\Models\TheoryQuestion;
use App\Support\MorphableHandler;

class TheoryQuestionController extends Controller
{
    public function index(Institution $institution, QuestionCourseable $morphable)
    {
        $this->authorizeCourseable($institution, $morphable);
        $morphable->loadParent();

        return view('ccd/theory-questions/index', [
            'allRecords' => paginateFromRequest(
                $morphable
                    ->theoryQuestions()
                    ->oldest('question_no')
                    ->oldest('question_sub_number')
            ),
            'courseable' => $morphable,
        ]);
    }

    public function create(Institution $institution, QuestionCourseable $morphable)
    {
        $this->authorizeCourseable($institution, $morphable);
        $morphable->loadParent();

        $lastQuestion = $morphable
            ->theoryQuestions()
            ->latest('question_no')
            ->first();
        $questionNumber = intval($lastQuestion?->question_no) + 1;

        return view('ccd/theory-questions/create-theory-question', [
            'edit' => null,
            'questionNumber' => $questionNumber,
            'courseable' => $morphable,
        ]);
    }

    public function store(
        Institution $institution,
        QuestionCourseable $morphable,
        TheoryQuestionRequest $request
    ) {
        $this->authorizeCourseable($institution, $morphable);

        $data = $request->validated();
        $data['institution_id'] = $institution->id;

        $morphable->theoryQuestions()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'question_no' => $data['question_no'],
                'question_sub_number' => $data['question_sub_number'] ?? null,
            ],
            $data
        );

        return $this->res(
            successRes('Theory question created'),
            instRoute('theory-questions.create', [$morphable->getMorphedId()])
        );
    }

    public function edit(Institution $institution, TheoryQuestion $theoryQuestion)
    {
        $this->authorizeTheoryQuestion($institution, $theoryQuestion);
        $courseable = $theoryQuestion->courseable;
        $courseable->loadParent();

        return view('ccd/theory-questions/create-theory-question', [
            'edit' => $theoryQuestion,
            'courseable' => $courseable,
            'questionNumber' => $theoryQuestion->question_no,
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
            instRoute('theory-questions.index', [
                MorphableHandler::make()->buildIdFromCourseable($theoryQuestion),
            ])
        );
    }

    public function destroy(Institution $institution, TheoryQuestion $theoryQuestion)
    {
        $this->authorizeTheoryQuestion($institution, $theoryQuestion);

        $institutionUser = currentInstitutionUser();
        abort_unless($institutionUser->isAdmin(), 403, 'Access denied');

        $courseableId = MorphableHandler::make()->buildIdFromCourseable($theoryQuestion);
        $theoryQuestion->delete();

        return $this->res(
            successRes('Theory question record deleted'),
            instRoute('theory-questions.index', [$courseableId])
        );
    }

    private function authorizeCourseable(
        Institution $institution,
        QuestionCourseable $courseable
    ): void {
        abort_unless(
            $this->courseableInstitutionId($courseable) === $institution->id,
            404
        );
    }

    private function authorizeTheoryQuestion(
        Institution $institution,
        TheoryQuestion $theoryQuestion
    ): void {
        abort_unless($theoryQuestion->institution_id === $institution->id, 404);
    }

    private function courseableInstitutionId(QuestionCourseable $courseable): int
    {
        if ($courseable instanceof CourseSession) {
            return $courseable->institution_id;
        }

        if ($courseable instanceof EventCourseable) {
            return $courseable->event->institution_id;
        }

        abort(404);
    }
}
