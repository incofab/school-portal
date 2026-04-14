<?php

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\TheoryQuestion;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->instAdmin = $this->institution->createdBy;
    $this->academicSession = AcademicSession::factory()->create();
    $this->course = Course::factory()
        ->withInstitution($this->institution)
        ->create();
    $this->courseSession = CourseSession::factory()
        ->course($this->course)
        ->create();
});

test('index displays theory questions for a course session', function () {
    TheoryQuestion::factory(3)
        ->courseSession($this->courseSession)
        ->create();

    $response = actingAs($this->instAdmin)->get(
        route('institutions.theory-questions.index', [
            $this->institution,
            $this->courseSession,
        ])
    );

    $response->assertOk();
    $response->assertViewIs('ccd.theory-questions.index');
    $response->assertViewHas('allRecords');
    $response->assertViewHas('courseSession');
    expect($response['allRecords']->count())->toBe(3);
});

test('create displays a form to record a theory question', function () {
    TheoryQuestion::factory()
        ->courseSession($this->courseSession)
        ->create(['question_number' => 4, 'question_sub_number' => 'a']);

    $response = actingAs($this->instAdmin)->get(
        route('institutions.theory-questions.create', [
            $this->institution,
            $this->courseSession,
        ])
    );

    $response->assertOk();
    $response->assertViewIs('ccd.theory-questions.create-theory-question');
    $response->assertViewHas('edit', null);
    $response->assertViewHas('courseSession');
    $response->assertViewHas('questionNumber', 5);
});

test('store creates a new theory question', function () {
    $data = TheoryQuestion::factory()
        ->courseSession($this->courseSession)
        ->raw([
            'question_number' => 1,
            'question_sub_number' => 'a',
            'question' => 'Explain photosynthesis.',
            'marks' => 5,
            'answer' => 'Photosynthesis is the process by which green plants make food.',
            'marking_scheme' => 'Mention chlorophyll, sunlight, carbon dioxide, water, and glucose.',
        ]);

    $response = actingAs($this->instAdmin)->post(
        route('institutions.theory-questions.store', [
            $this->institution,
            $this->courseSession,
        ]),
        $data
    );

    $response->assertRedirect();
    expect(TheoryQuestion::count())->toBe(1);
    $this->assertDatabaseHas('theory_questions', [
        'institution_id' => $this->institution->id,
        'course_session_id' => $this->courseSession->id,
        'question_number' => 1,
        'question_sub_number' => 'a',
        'question' => 'Explain photosynthesis.',
        'marks' => 5,
    ]);
});

test('edit displays a form to edit a theory question', function () {
    $theoryQuestion = TheoryQuestion::factory()
        ->courseSession($this->courseSession)
        ->create();

    $response = actingAs($this->instAdmin)->get(
        route('institutions.theory-questions.edit', [
            $this->institution,
            $theoryQuestion,
        ])
    );

    $response->assertOk();
    $response->assertViewIs('ccd.theory-questions.create-theory-question');
    $response->assertViewHas('edit', $theoryQuestion);
    $response->assertViewHas('courseSession');
    $response->assertViewHas('questionNumber', $theoryQuestion->question_number);
});

test('updates an existing theory question', function () {
    $theoryQuestion = TheoryQuestion::factory()
        ->courseSession($this->courseSession)
        ->create(['question_number' => 1, 'question_sub_number' => 'a']);

    $newData = TheoryQuestion::factory()
        ->courseSession($this->courseSession)
        ->raw([
            'question_number' => 2,
            'question_sub_number' => 'b',
            'question' => 'Updated Theory Question Text',
            'marks' => 7.5,
            'answer' => 'Updated answer',
            'marking_scheme' => 'Updated marking scheme',
        ]);

    $response = actingAs($this->instAdmin)->put(
        route('institutions.theory-questions.update', [
            $this->institution,
            $theoryQuestion,
        ]),
        $newData
    );

    $response->assertRedirect();
    $theoryQuestion->refresh();
    expect($theoryQuestion->question)->toBe('Updated Theory Question Text');
    expect($theoryQuestion->question_number)->toBe(2);
    expect($theoryQuestion->question_sub_number)->toBe('b');
    expect($theoryQuestion->marks)->toBe(7.5);
});

test('deletes a theory question', function () {
    $theoryQuestion = TheoryQuestion::factory()
        ->courseSession($this->courseSession)
        ->create();

    $response = actingAs($this->instAdmin)->get(
        route('institutions.theory-questions.destroy', [
            $this->institution,
            $theoryQuestion,
        ])
    );

    $response->assertRedirect();
    expect(TheoryQuestion::count())->toBe(0);
});
