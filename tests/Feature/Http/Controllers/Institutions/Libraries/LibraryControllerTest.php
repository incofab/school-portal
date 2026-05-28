<?php

use App\Enums\InstitutionUserType;
use App\Enums\LibrarySourceType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Library;
use App\Models\Student;
use App\Models\User;
use App\Support\SettingsHandler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * ./vendor/bin/pest --filter LibraryControllerTest
 */
beforeEach(function () {
  Storage::fake('s3_public');
  AcademicSession::factory()->create();
  SettingsHandler::clear();

  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->teacher = User::factory()->create();
  $this->studentUser = User::factory()->create();

  $this->adminInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $this->admin->id,
    'role' => InstitutionUserType::Admin->value
  ]);

  $this->teacherInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $this->teacher->id,
    'role' => InstitutionUserType::Teacher->value
  ]);

  $this->studentInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $this->studentUser->id,
    'role' => InstitutionUserType::Student->value
  ]);

  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->course = Course::factory()->create([
    'institution_id' => $this->institution->id
  ]);
  $this->otherClassification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  Student::factory()
    ->withInstitution(
      $this->institution,
      $this->classification,
      $this->studentInstitutionUser
    )
    ->create();
});

it('allows an admin to create a public external library material', function () {
  actingAs($this->admin)
    ->postJson(route('institutions.libraries.store', $this->institution), [
      'institution_user_id' => $this->adminInstitutionUser->id,
      'title' => 'SS2 Biology Video',
      'course_id' => $this->course->id,
      'material_type' => 'video',
      'source_type' => LibrarySourceType::External->value,
      'description' => 'Cell division class material',
      'external_url' => 'https://example.com/biology-video',
      'classification_ids' => []
    ])
    ->assertOk();

  assertDatabaseHas('libraries', [
    'institution_id' => $this->institution->id,
    'title' => 'SS2 Biology Video',
    'course_id' => $this->course->id,
    'source_type' => LibrarySourceType::External->value,
    'is_public' => true,
    'external_url' => 'https://example.com/biology-video'
  ]);
});

it(
  'allows an admin to create a class-targeted uploaded library material',
  function () {
    $file = UploadedFile::fake()->create(
      'lesson-note.pdf',
      256,
      'application/pdf'
    );

    actingAs($this->admin)
      ->postJson(route('institutions.libraries.store', $this->institution), [
        'institution_user_id' => $this->adminInstitutionUser->id,
        'title' => 'SS2 Lesson Note',
        'material_type' => 'pdf',
        'source_type' => LibrarySourceType::Upload->value,
        'file' => $file,
        'classification_ids' => [$this->classification->id]
      ])
      ->assertOk();

    assertDatabaseHas('libraries', [
      'institution_id' => $this->institution->id,
      'title' => 'SS2 Lesson Note',
      'is_public' => false,
      'file_name' => 'lesson-note.pdf'
    ]);

    assertDatabaseHas('library_classifications', [
      'institution_id' => $this->institution->id,
      'classification_id' => $this->classification->id
    ]);
  }
);

it('rejects uploaded library files larger than 1mb', function () {
  $file = UploadedFile::fake()->create('large.pdf', 1025, 'application/pdf');

  actingAs($this->admin)
    ->postJson(route('institutions.libraries.store', $this->institution), [
      'institution_user_id' => $this->adminInstitutionUser->id,
      'title' => 'Large material',
      'material_type' => 'pdf',
      'source_type' => LibrarySourceType::Upload->value,
      'file' => $file
    ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['file']);
});

it(
  'only shows students public and matching class library materials',
  function () {
    Library::factory()->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id,
      'title' => 'General Handbook',
      'is_public' => true
    ]);
    Library::factory()
      ->withClassifications(collect([$this->classification]))
      ->create([
        'institution_id' => $this->institution->id,
        'institution_user_id' => $this->teacherInstitutionUser->id,
        'title' => 'Student Class Note'
      ]);
    Library::factory()
      ->withClassifications(collect([$this->otherClassification]))
      ->create([
        'institution_id' => $this->institution->id,
        'institution_user_id' => $this->teacherInstitutionUser->id,
        'title' => 'Other Class Note'
      ]);

    actingAs($this->studentUser)
      ->get(route('institutions.libraries.index', $this->institution))
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/libraries/list-libraries')
          ->has('libraries.data', 2)
      );
  }
);

it(
  'allows the owner teacher to update and delete a library material',
  function () {
    $library = Library::factory()
      ->withClassifications(collect([$this->classification]))
      ->create([
        'institution_id' => $this->institution->id,
        'institution_user_id' => $this->teacherInstitutionUser->id
      ]);

    actingAs($this->teacher)
      ->putJson(
        route('institutions.libraries.update', [$this->institution, $library]),
        [
          'institution_user_id' => $this->teacherInstitutionUser->id,
          'title' => 'Updated Library Material',
          'material_type' => 'document',
          'source_type' => LibrarySourceType::External->value,
          'external_url' => 'https://example.com/updated',
          'classification_ids' => []
        ]
      )
      ->assertOk();

    assertDatabaseHas('libraries', [
      'id' => $library->id,
      'title' => 'Updated Library Material',
      'is_public' => true
    ]);

    actingAs($this->teacher)
      ->deleteJson(
        route('institutions.libraries.destroy', [$this->institution, $library])
      )
      ->assertOk();

    assertDatabaseMissing('libraries', ['id' => $library->id]);
  }
);
