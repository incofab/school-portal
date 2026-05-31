<?php

use App\Actions\CourseResult\GenerateAiTermResultComments;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;

class FakeGenerateAiTermResultComments extends GenerateAiTermResultComments
{
    public array $chunks = [];

    protected function generateComments(array $payload): array
    {
        $this->chunks[] = $payload;

        return collect($payload)
            ->map(
                fn (array $item) => [
                    'term_result_id' => $item['term_result_id'],
                    'teacher_comment' => 'Teacher comment for '.$item['student']['name'],
                    'principal_comment' => 'Principal comment for '.$item['student']['name'],
                ]
            )
            ->all();
    }
}

it('generates term result comments in chunks of ten', function () {
    $institution = Institution::factory()->create();
    $classification = Classification::factory()
        ->withInstitution($institution)
        ->create();
    $academicSession = AcademicSession::factory()->create();
    $course = Course::factory()
        ->withInstitution($institution)
        ->create(['title' => 'Mathematics', 'code' => 'MTH']);
    $classResultInfo = ClassResultInfo::factory()
        ->classification($classification)
        ->create([
            'academic_session_id' => $academicSession->id,
            'term' => TermType::First,
            'for_mid_term' => false,
        ]);

    $termResults = collect(range(1, 11))->map(function () use (
        $institution,
        $classification,
        $academicSession,
        $course
    ) {
        $student = Student::factory()
            ->withInstitution($institution, $classification)
            ->create();
        $termResult = TermResult::factory()
            ->forStudent($student)
            ->create([
                'academic_session_id' => $academicSession->id,
                'classification_id' => $classification->id,
                'term' => TermType::First,
                'for_mid_term' => false,
                'average' => 65,
            ]);
        CourseResult::factory()
            ->fromTermResult($termResult)
            ->create([
                'course_id' => $course->id,
                'result' => 65,
                'exam' => 40,
            ]);

        return $termResult;
    });

    $action = new FakeGenerateAiTermResultComments;
    $updatedCount = $action->run($classResultInfo);

    expect($updatedCount)->toBe(11)
        ->and($action->chunks)->toHaveCount(2)
        ->and($action->chunks[0])->toHaveCount(10)
        ->and($action->chunks[1])->toHaveCount(1);

    $termResults->each(function (TermResult $termResult) {
        $termResult->refresh();
        expect(str_starts_with($termResult->teacher_comment, 'Teacher comment for '))
            ->toBeTrue()
            ->and(str_starts_with($termResult->principal_comment, 'Principal comment for '))
            ->toBeTrue();
    });
});

it('only submits incomplete comments and does not overwrite existing comments', function () {
    $institution = Institution::factory()->create();
    $classification = Classification::factory()
        ->withInstitution($institution)
        ->create();
    $academicSession = AcademicSession::factory()->create();
    $course = Course::factory()
        ->withInstitution($institution)
        ->create(['title' => 'English Language', 'code' => 'ENG']);
    $classResultInfo = ClassResultInfo::factory()
        ->classification($classification)
        ->create([
            'academic_session_id' => $academicSession->id,
            'term' => TermType::First,
            'for_mid_term' => false,
        ]);

    $createTermResult = function (array $attributes = []) use (
        $institution,
        $classification,
        $academicSession,
        $course
    ) {
        $student = Student::factory()
            ->withInstitution($institution, $classification)
            ->create();
        $termResult = TermResult::factory()
            ->forStudent($student)
            ->create([
                'academic_session_id' => $academicSession->id,
                'classification_id' => $classification->id,
                'term' => TermType::First,
                'for_mid_term' => false,
                'average' => 72,
                ...$attributes,
            ]);
        CourseResult::factory()
            ->fromTermResult($termResult)
            ->create([
                'course_id' => $course->id,
                'result' => 72,
                'exam' => 45,
            ]);

        return $termResult;
    };

    $completeTermResult = $createTermResult([
        'teacher_comment' => 'Existing teacher comment.',
        'principal_comment' => 'Existing principal comment.',
    ]);
    $teacherCommentOnlyTermResult = $createTermResult([
        'teacher_comment' => 'Keep this teacher comment.',
        'principal_comment' => null,
    ]);
    $blankTermResult = $createTermResult();

    $action = new FakeGenerateAiTermResultComments;
    $updatedCount = $action->run($classResultInfo);

    expect($updatedCount)->toBe(2)
        ->and($action->chunks)->toHaveCount(1)
        ->and(collect($action->chunks[0])->pluck('term_result_id')->all())
        ->toEqualCanonicalizing([
            $teacherCommentOnlyTermResult->id,
            $blankTermResult->id,
        ]);

    $completeTermResult->refresh();
    $teacherCommentOnlyTermResult->refresh();
    $blankTermResult->refresh();

    expect($completeTermResult->teacher_comment)->toBe('Existing teacher comment.')
        ->and($completeTermResult->principal_comment)->toBe('Existing principal comment.')
        ->and($teacherCommentOnlyTermResult->teacher_comment)->toBe('Keep this teacher comment.')
        ->and(str_starts_with($teacherCommentOnlyTermResult->principal_comment, 'Principal comment for '))
        ->toBeTrue()
        ->and(str_starts_with($blankTermResult->teacher_comment, 'Teacher comment for '))
        ->toBeTrue()
        ->and(str_starts_with($blankTermResult->principal_comment, 'Principal comment for '))
        ->toBeTrue();
});
