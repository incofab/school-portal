<?php

use App\Models\AcademicSession;
use App\Models\CourseResult;
use App\Models\TermResult;
use App\Models\Institution;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  [$this->termResult, $this->termResult2] = TermResult::factory(2)
    ->withInstitution($this->institution)
    ->for($this->academicSession)
    ->create();
  CourseResult::factory()
    ->fromTermResult($this->termResult)
    ->create();
  CourseResult::factory()
    ->fromTermResult($this->termResult2)
    ->create();
});

it('should delete a term result', function () {
  $this->actingAs($this->instAdmin);
  $this->deleteJson(
    route('institutions.term-results.destroy', [
      $this->institution,
      $this->termResult->id
    ])
  )->assertStatus(200);
  expect($this->termResult->fresh())->toBeNull();
  expect(
    CourseResult::query()
      ->where([
        'academic_session_id' => $this->termResult->academic_session_id,
        'student_id' => $this->termResult->student_id,
        'course_id' => $this->termResult->course_id,
        'term' => $this->termResult->term,
        'for_mid_term' => $this->termResult->for_mid_term,
        'classification_id' => $this->termResult->classification_id
      ])
      ->first()
  )->toBeNull();
});
