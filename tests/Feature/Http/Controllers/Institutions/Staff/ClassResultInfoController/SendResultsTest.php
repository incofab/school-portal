<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;
use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->term = TermType::First;

  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->classResultInfo = ClassResultInfo::factory()->create([
    'institution_id' => $this->institution->id,
    'classification_id' => $this->classification->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term,
    'for_mid_term' => false,
    'whatsapp_message_count' => 0
  ]);

  $this->student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();

  // Create a TermResult that is published and activated
  $this->termResult = TermResult::factory()
    ->forStudent($this->student)
    ->published()
    ->create([
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term,
      'for_mid_term' => false,
      'is_activated' => true
    ]);

  $this->route = route('institutions.class-result-info.send-results', [
    $this->institution->uuid,
    $this->classResultInfo
  ]);
  Http::fake([
    'https://graph.facebook.com/*' => Http::response([
      'requestSuccessful' => true
    ])
  ]);
});

it('sends term results to guardians', function () {
  // Attempt to send
  actingAs($this->instAdmin)
    ->postJson($this->route)
    ->assertOk();

  // Check if message count increased
  expect(
    $this->classResultInfo->fresh()->whatsapp_message_count
  )->toBeGreaterThan(0);
});

it('fails to send results if user is not admin', function () {
  $teacher = \App\Models\InstitutionUser::factory()
    ->teacher()
    ->withInstitution($this->institution)
    ->create()->user;

  actingAs($teacher)
    ->postJson($this->route)
    ->assertForbidden();
});

it('fails if no term results found', function () {
  // Delete the term result to simulate "no results"
  $this->termResult->delete();

  actingAs($this->instAdmin)
    ->postJson($this->route)
    ->assertNotFound();
});
