<?php

use App\Models\Institution;
use App\Models\Assignment;
use App\Models\CourseTeacher;
use App\Models\Student;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
    $this->assignment = Assignment::factory()->withInstitution($this->institution)->create(['max_score' => 20]);
    $this->studentUser = Student::factory()->withInstitution($this->institution)->create();

    // Get the course teacher already linked to the 'assignment'
    $this->courseTeacher = $this->assignment->courseTeacher->user;

    // Add a submission to the assignment with the correct student ID
    $this->submission = $this->assignment->assignmentSubmissions()->create([
        'student_id' => $this->studentUser->id,
        'assignment_id' => $this->assignment->id,
        'answer' => 'Sample submission content',
    ]);
});

//== store()
it('allows a student to submit an assignment', function () {
    // Define the route for submitting an assignment
    $route = route('institutions.assignment-submissions.store', [
        'institution' => $this->institution->uuid
    ]);

    // Create the payload for the submission
    $payload = [
        'assignment_id' => $this->assignment->id,
        'answer' => 'This is a sample answer.',
    ];

    // Act as the student user and submit the assignment
    actingAs($this->studentUser->user)->postJson($route, $payload)
        ->assertOk();

    // Assert that the submission has been created in the database
    assertDatabaseHas('assignment_submissions', [
        'assignment_id' => $this->assignment->id,
        'student_id' => $this->studentUser->id,
        'answer' => 'This is a sample answer.',
    ]);
});

it('prevents non-students from submitting an assignment', function () {
    // Define the route for submitting an assignment
    $route = route('institutions.assignment-submissions.store', [
        'institution' => $this->institution->uuid
    ]);

    // Attempt to submit as a non-student user
    actingAs($this->admin)->postJson($route, [
        'assignment_id' => $this->assignment->id,
        'answer' => 'This should not be allowed.',
    ])->assertStatus(401);
});

//== score()
it('allows an authorized user to score an assignment submission', function () {
    $route = route('institutions.assignment-submission.score', [
        'institution' => $this->institution->uuid,
        'assignmentSubmission' => $this->submission->id,
    ]);

    $payload = [
        'score' => 15,
        'remark' => 'Good work',
    ];

    // Act as the course teacher and score the submission
    actingAs($this->courseTeacher)->postJson($route, $payload)
        ->assertOk();

    // Assert that the score and remark were saved to the database
    assertDatabaseHas('assignment_submissions', [
        'id' => $this->submission->id,
        'score' => 15,
        'remark' => 'Good work',
    ]);
});

it('prevents scoring with an invalid score', function () {
    $route = route('institutions.assignment-submission.score', [
        'institution' => $this->institution->uuid,
        'assignmentSubmission' => $this->submission->id,
    ]);

    // Try a score above the max_score
    $payload = [
        'score' => 50,
        'remark' => 'Exceeds max score',
    ];

    actingAs($this->courseTeacher)->postJson($route, $payload)
        ->assertJsonValidationErrors(['score']);

    // Try a negative score
    $payload = [
        'score' => -10,
        'remark' => 'Negative score',
    ];

    actingAs($this->courseTeacher)->postJson($route, $payload)
        ->assertJsonValidationErrors(['score']);
});

it('prevents unauthorized users from scoring an assignment submission', function () {
    $route = route('institutions.assignment-submission.score', [
        'institution' => $this->institution->uuid,
        'assignmentSubmission' => $this->submission->id,
    ]);

    $payload = [
        'score' => 17,
        'remark' => 'Unauthorized user scoring',
    ];

    // Attempt to score as a student
    actingAs($this->studentUser->user)->postJson($route, $payload)
        ->assertStatus(403);

    // Attempt to score as an unrelated teacher (not assigned to the course)
    $unrelatedTeacher = CourseTeacher::factory()->withInstitution($this->institution)->create();
    actingAs($unrelatedTeacher->user)->postJson($route, $payload)
        ->assertStatus(403);
});