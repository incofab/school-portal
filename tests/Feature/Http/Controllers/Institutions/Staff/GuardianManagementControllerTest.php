<?php

use App\Enums\GuardianRelationship;
use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function PHPUnit\Framework\assertNotNull;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->students = Student::factory(5)
    ->withInstitution($this->institution)
    ->create();
  $this->instAdmin = $this->institution->createdBy;
});

it('should render the create view', function () {
  actingAs($this->instAdmin)
    ->getJson(
      route('institutions.guardians.classifications.create', [
        $this->institution->uuid,
        $this->classification
      ])
    )
    ->assertStatus(200);
});

it('should store guardians for students', function () {
  $guardiansData = [];
  foreach ($this->students as $student) {
    $guardiansData[$student->id] = [
      ...User::factory()
        ->make()
        ->only(['first_name', 'last_name', 'other_names', 'email', 'phone']),
      'relationship' => GuardianRelationship::Parent->value
    ];
  }

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.guardians.classifications.store', [
        $this->institution->uuid,
        $this->classification
      ]),
      [
        'guardians' => [
          '000' => User::factory()
            ->make()
            ->only([
              'first_name',
              'last_name',
              'other_names',
              'email',
              'phone'
            ]),
          ...$guardiansData
        ]
      ]
    )
    ->assertJsonValidationErrorFor('message');

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.guardians.classifications.store', [
        $this->institution->uuid,
        $this->classification
      ]),
      ['guardians' => $guardiansData]
    )
    ->assertStatus(200);

  // Assert guardians were properly created
  foreach ($this->students as $student) {
    $guardianUser = User::where(
      'email',
      $guardiansData[$student->id]['email']
    )->first();
    assertNotNull($guardianUser);
    $this->assertDatabaseHas('users', [
      'email' => $guardianUser->email
    ]);
    $this->assertDatabaseHas('institution_users', [
      'user_id' => $guardianUser->id,
      'institution_id' => $this->institution->id,
      'role' => InstitutionUserType::Guardian
    ]);
    $this->assertDatabaseHas('guardian_students', [
      'guardian_user_id' => $guardianUser->id,
      'student_id' => $student->id,
      'relationship' => $guardiansData[$student->id]['relationship']
    ]);
  }
});
