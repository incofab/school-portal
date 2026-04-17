<?php

use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  ClassResultInfo::clearResultLockCache();

  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->teacher = User::factory()
    ->teacher($this->institution)
    ->create();
  $this->academicSession = AcademicSession::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->courseResultInfo = CourseResultInfo::factory()
    ->withInstitution(
      $this->institution,
      $this->classification,
      $this->course,
      $this->academicSession
    )
    ->create();
});

test(
  'admin can delete course result info along with course results',
  function () {
    $courseResult = CourseResult::factory()
      ->forCourseResultInfo($this->courseResultInfo)
      ->create();

    actingAs($this->admin)
      ->deleteJson(
        route('institutions.course-result-info.destroy', [
          $this->institution,
          $this->courseResultInfo
        ])
      )
      ->assertOk();

    assertDatabaseMissing('course_result_info', [
      'id' => $this->courseResultInfo->id
    ]);
    assertDatabaseMissing('course_results', [
      'id' => $courseResult->id
    ]);
  }
);

test('teacher cannot delete course result info', function () {
  actingAs($this->teacher)
    ->deleteJson(
      route('institutions.course-result-info.destroy', [
        $this->institution,
        $this->courseResultInfo
      ])
    )
    ->assertForbidden();

  assertDatabaseHas('course_result_info', [
    'id' => $this->courseResultInfo->id
  ]);
});

test(
  'admin cannot delete course result info for a locked class result',
  function () {
    ClassResultInfo::factory()
      ->classification($this->classification)
      ->create([
        'academic_session_id' => $this->academicSession->id,
        'term' => $this->courseResultInfo->term,
        'for_mid_term' => false,
        'is_locked' => true
      ]);

    actingAs($this->admin)
      ->deleteJson(
        route('institutions.course-result-info.destroy', [
          $this->institution,
          $this->courseResultInfo
        ])
      )
      ->assertStatus(423);

    assertDatabaseHas('course_result_info', [
      'id' => $this->courseResultInfo->id
    ]);
  }
);
