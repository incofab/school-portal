<?php

use App\Models\AdmissionApplication;
use App\Models\ApplicationGuardian;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;

use Illuminate\Support\Facades\Request;
use Mockery\MockInterface;
use App\Actions\HandleAdmission;
use App\Models\Assignment;
use App\Models\Classification;
use App\Models\CourseTeacher;
use App\Models\Student;
use Database\Factories\ApplicationGuardianFactory;
use Database\Factories\StudentFactory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
    Storage::fake();
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
    $this->assignment = Assignment::factory()->withInstitution($this->institution)->create();

    // Get the course teacher already linked to the 'assignment'
    $this->courseTeacher = $this->assignment->courseTeacher->user;
});

it('creates an assignment', function () {
    $route = route('institutions.assignments.store', [
        'institution' => $this->institution->uuid
    ]);

    actingAs($this->admin)->postJson($route, [])
        ->assertJsonValidationErrors(['max_score', 'content', 'expires_at']);

    $student = User::factory()->student()->create();
    actingAs($student)->postJson($route, [])->assertForbidden();

    $assignmentData = Assignment::factory()
        ->make()->toArray();

    actingAs($this->admin)->postJson($route, $assignmentData)
        ->assertOk();

    assertDatabaseHas('assignments', collect($assignmentData)->only('max_score', 'expires_at', 'course_teacher_id')->toArray());
});

it('allows only the CourseTeacher or Admin to edit an assignment', function () {
    $route = route('institutions.assignments.update', [
        'institution' => $this->institution->uuid,
        'assignment' => $this->assignment->id,
    ]);

    // Data to update the assignment
    $updatedData = [
        'course_teacher_id' => $this->assignment->course_teacher_id,
        'content' => $this->assignment->content,
        'max_score' => 90,
        'expires_at' => now()->addDays(10)->toDateTimeString(),
    ];

    // Unauthorized User (e.g., Student) tries to edit the assignment
    $student = User::factory()->student()->create();
    actingAs($student)->putJson($route, $updatedData)->assertForbidden();

    // Authorized as Admin
    actingAs($this->admin)->putJson($route, $updatedData)->assertOk();
    assertDatabaseHas('assignments', array_merge(['id' => $this->assignment->id], $updatedData));

    $updatedData['max_score'] = 92;
    // Authorized as CourseTeacher
    actingAs($this->courseTeacher)->putJson($route, $updatedData)->assertOk();
    assertDatabaseHas('assignments', array_merge(['id' => $this->assignment->id], $updatedData));

    // Unauthorized User (non-related Teacher) tries to edit the assignment
    $unrelatedTeacher = CourseTeacher::factory()->withInstitution($this->institution)->create();
    actingAs($unrelatedTeacher->user)->putJson($route, $updatedData)->assertForbidden();
});

it('allows only the assigned CourseTeacher or Admin to delete an assignment', function () {
    // Initial route for the first instance of the assignment
    $route = route('institutions.assignments.destroy', [
        'institution' => $this->institution->uuid,
        'assignment' => $this->assignment->id,
    ]);

    // Unauthorized User (e.g., unrelated Teacher) tries to delete the assignment
    $unrelatedTeacher = CourseTeacher::factory()->withInstitution($this->institution)->create();
    actingAs($unrelatedTeacher->user)->deleteJson($route)->assertStatus(401);

    // Unauthorized User (e.g., Student) tries to delete the assignment
    $student = User::factory()->student()->create();
    actingAs($student)->deleteJson($route)->assertForbidden();

    // Authorized User (Admin) deletes the assignment
    actingAs($this->admin)->deleteJson($route)->assertOk();
    assertDatabaseMissing('assignments', ['id' => $this->assignment->id]);

    // Re-create the assignment for further testing and update `$this->assignment`
    $this->assignment = Assignment::factory()->withInstitution($this->institution)->create();

    // Re-establish the route with the updated assignment's ID
    $route = route('institutions.assignments.destroy', [
        'institution' => $this->institution->uuid,
        'assignment' => $this->assignment->id,
    ]);

    // Authorized User (assigned CourseTeacher) deletes the new assignment
    $courseTeacherUser = $this->assignment->courseTeacher->user;  // Get the CourseTeacher associated with the assignment
    actingAs($courseTeacherUser)->deleteJson($route)->assertOk();
    assertDatabaseMissing('assignments', ['id' => $this->assignment->id]);
});

it('prevents deletion if the assignment has submissions', function () {
    $route = route('institutions.assignments.destroy', [
        'institution' => $this->institution->uuid,
        'assignment' => $this->assignment->id,
    ]);

    // Create a student record in the `students` table
    $student = Student::factory()->withInstitution($this->institution)->create();

    // Add a submission to the assignment with the correct student ID
    $this->assignment->assignmentSubmissions()->create([
        'student_id' => $student->id,
        'assignment_id' => $this->assignment->id,
        'answer' => 'Sample submission content',
    ]);

    // Try to delete as an Admin (should fail due to existing 'assignment submission')
    actingAs($this->admin)->deleteJson($route)->assertForbidden();
});