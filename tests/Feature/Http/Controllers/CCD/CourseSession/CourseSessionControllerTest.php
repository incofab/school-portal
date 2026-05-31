<?php

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
    $this->course = Course::factory()
        ->withInstitution($this->institution)
        ->create();
    $this->courseSession = CourseSession::factory()
        ->course($this->course)
        ->create();

    $this->assignedTeacher = User::factory()
        ->teacher($this->institution)
        ->create();
    CourseTeacher::factory()->create([
        'institution_id' => $this->institution->id,
        'course_id' => $this->course->id,
        'user_id' => $this->assignedTeacher->id,
    ]);

    $this->otherTeacher = User::factory()
        ->teacher($this->institution)
        ->create();
});

test('assigned course teacher can view a course sessions list', function () {
    actingAs($this->assignedTeacher)
        ->get(
            route('institutions.course-sessions.index', [
                $this->institution,
                $this->course,
            ])
        )
        ->assertOk()
        ->assertViewIs('ccd.course-sessions.index');
});

test('unassigned teacher cannot view a course sessions list', function () {
    actingAs($this->otherTeacher)
        ->get(
            route('institutions.course-sessions.index', [
                $this->institution,
                $this->course,
            ])
        )
        ->assertForbidden();
});

test('assigned course teacher can create a course session', function () {
    actingAs($this->assignedTeacher)
        ->post(
            route('institutions.course-sessions.store', [
                $this->institution,
                $this->course,
            ]),
            [
                'session' => '2020',
                'category' => 'exam',
                'general_instructions' => 'Answer all questions',
            ]
        )
        ->assertRedirect();

    $this->assertDatabaseHas('course_sessions', [
        'institution_id' => $this->institution->id,
        'course_id' => $this->course->id,
        'session' => '2020',
    ]);
});

test('unassigned teacher cannot delete a course session', function () {
    actingAs($this->otherTeacher)
        ->get(
            route('institutions.course-sessions.destroy', [
                $this->institution,
                $this->courseSession,
            ])
        )
        ->assertForbidden();
});

test(
    'assigned course teacher permanently deletes a course session with no existing references',
    function () {
        actingAs($this->assignedTeacher)
            ->get(
                route('institutions.course-sessions.destroy', [
                    $this->institution,
                    $this->courseSession,
                ])
            )
            ->assertRedirect();

        assertDatabaseMissing('course_sessions', [
            'id' => $this->courseSession->id,
        ]);
    }
);

test(
    'assigned course teacher cannot delete a course session with existing references',
    function () {
        $courseSession = CourseSession::factory()
            ->course($this->course)
            ->questions()
            ->create();

        actingAs($this->assignedTeacher)
            ->get(
                route('institutions.course-sessions.destroy', [
                    $this->institution,
                    $courseSession,
                ])
            )
            ->assertStatus(400);

        assertDatabaseHas('course_sessions', [
            'id' => $courseSession->id,
            'deleted_at' => null,
        ]);
    }
);
