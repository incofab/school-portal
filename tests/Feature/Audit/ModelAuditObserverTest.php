<?php

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Support\Audit\ActivityLogSanitizer;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    ActivityLog::query()->delete();
});

it('creates an audit log when an opted-in model is created', function () {
    $institution = Institution::factory()->create();
    ActivityLog::query()->delete();

    actingAs($institution->createdBy);

    $course = Course::factory()
        ->withInstitution($institution)
        ->create(['title' => 'Biology', 'code' => 'BIO']);

    $log = ActivityLog::query()
        ->where('event', 'model.course.created')
        ->firstOrFail();

    expect($log->action)->toBe('created')
        ->and($log->subject_type)->toBe(Course::class)
        ->and($log->subject_id)->toBe($course->id)
        ->and($log->actor_id)->toBe($institution->createdBy->id)
        ->and($log->new_values->toArray())->toMatchArray([
            'title' => 'Biology',
            'code' => 'BIO',
            'institution_id' => $institution->id,
        ]);
});

it('records old and new values when an opted-in model is updated', function () {
    $institution = Institution::factory()->create();
    $course = Course::factory()
        ->withInstitution($institution)
        ->create(['title' => 'Chemistry', 'code' => 'CHEM']);
    ActivityLog::query()->delete();

    $course->update(['title' => 'Advanced Chemistry']);

    $log = ActivityLog::query()
        ->where('event', 'model.course.updated')
        ->firstOrFail();

    expect($log->old_values->toArray())->toBe(['title' => 'Chemistry'])
        ->and($log->new_values->toArray())->toBe(['title' => 'Advanced Chemistry']);
});

it('creates an audit log when an opted-in model is deleted', function () {
    $institution = Institution::factory()->create();
    $course = Course::factory()
        ->withInstitution($institution)
        ->create(['title' => 'Physics', 'code' => 'PHY']);
    ActivityLog::query()->delete();

    $course->delete();

    $log = ActivityLog::query()
        ->where('event', 'model.course.deleted')
        ->firstOrFail();

    expect($log->action)->toBe('deleted')
        ->and($log->subject_type)->toBe(Course::class)
        ->and($log->subject_id)->toBe($course->id)
        ->and($log->old_values->toArray())->toMatchArray([
            'title' => 'Physics',
            'code' => 'PHY',
        ]);
});

it('redacts sensitive fields in automatic model audit diffs', function () {
    $user = User::factory()->create();
    ActivityLog::query()->delete();

    $user->update([
        'password' => 'new-secret-password',
        'remember_token' => 'new-token',
        'first_name' => 'Visible',
    ]);

    $log = ActivityLog::query()
        ->where('event', 'model.user.updated')
        ->firstOrFail();

    expect($log->new_values->toArray())->toBe([
        'password' => ActivityLogSanitizer::REDACTED,
        'first_name' => 'Visible',
    ]);
});

it('ignores timestamp-only automatic model updates', function () {
    $institution = Institution::factory()->create();
    $course = Course::factory()
        ->withInstitution($institution)
        ->create();
    ActivityLog::query()->delete();

    $course->touch();

    expect(ActivityLog::query()->where('event', 'model.course.updated')->count())->toBe(0);
});

it('sets institution scope for institution-owned models and related models', function () {
    $institution = Institution::factory()->create();
    $student = Student::factory()
        ->withInstitution($institution)
        ->create();
    ActivityLog::query()->delete();

    $student->update(['guardian_phone' => '08030000000']);

    $log = ActivityLog::query()
        ->where('event', 'model.student.updated')
        ->firstOrFail();

    expect($log->institution_id)->toBe($institution->id)
        ->and($log->institution_group_id)->toBe($institution->institution_group_id);
});

it('records automatic model audits from console context without an actor', function () {
    $institution = Institution::factory()->create();
    ActivityLog::query()->delete();
    Auth::logout();

    Course::factory()
        ->withInstitution($institution)
        ->create(['title' => 'CLI Course', 'code' => 'CLI']);

    $log = ActivityLog::query()
        ->where('event', 'model.course.created')
        ->firstOrFail();

    expect($log->actor_id)->toBeNull()
        ->and($log->institution_id)->toBe($institution->id)
        ->and($log->new_values->toArray())->toMatchArray(['title' => 'CLI Course']);
});
