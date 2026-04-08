<?php

namespace App\Http\Controllers;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Student;
use App\Support\ExamHandler;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicExamResultController extends Controller
{
    public function create()
    {
        return Inertia::render('auth/exam-result-login', [
            'institutionGroup' => getInstitutionGroupFromDomain(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'exam_no' => ['required', 'string'],
        ]);

        $exam = Exam::query()
            ->where('exam_no', trim($data['exam_no']))
            ->firstOrFail();

        return redirect()->route('exam-results.show', [$exam->exam_no]);
    }

    public function show(Exam $exam)
    {
        $exam = $this->loadExam($exam);

        abort_unless(
            $exam->examable instanceof Student,
            403,
            'This exam result is only available for student exams'
        );

        $examHandler = ExamHandler::make($exam);

        abort_if(
            $examHandler->canRun(false),
            403,
            'You cannot view results when exam is still active'
        );

        if ($exam->status !== ExamStatus::Ended) {
            $examHandler->endExam();
            $exam = $this->loadExam($exam->fresh());
        }

        return Inertia::render('auth/exam-result-sheet', [
            'exam' => $exam,
            'institution' => $exam->institution,
        ]);
    }

    private function loadExam(Exam $exam): Exam
    {
        return Exam::query()
            ->whereKey($exam->id)
            ->with('event', 'institution.institutionGroup')
            ->with(
                'examCourseables.courseable',
                fn ($q) => $q->with('course', 'questions')
            )
            ->with([
                'examable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([Student::class => ['user', 'classification']]);
                },
            ])
            ->firstOrFail();
    }
}
