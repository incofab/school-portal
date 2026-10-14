<?php

use App\Enums\InstitutionUserType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Designation;
use App\Models\DesignationAssignment;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->classA = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classB = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->session = AcademicSession::factory()->create();
  $this->route = fn(string $name, array $params = []) => route(
    'institutions.' . $name,
    [$this->institution->uuid, ...$params]
  );
});

function designationPayload(array $overrides = []): array
{
  return array_merge(
    [
      'title' => 'Head Prefect',
      'description' => 'Student leadership position',
      'is_for_students' => true,
      'scope' => 'institution',
      'max_holders' => 1,
      'is_active' => true,
      'eligible_class_ids' => []
    ],
    $overrides
  );
}

it(
  'allows an administrator to create student and staff designations',
  function () {
    actingAs($this->admin)
      ->post(
        $this->route('designations.store'),
        designationPayload([
          'eligible_class_ids' => [$this->classA->id, $this->classB->id]
        ])
      )
      ->assertOk();
    actingAs($this->admin)
      ->post(
        $this->route('designations.store'),
        designationPayload([
          'title' => 'Examination Officer',
          'is_for_students' => false,
          'scope' => 'institution',
          'eligible_class_ids' => []
        ])
      )
      ->assertOk();

    expect(Designation::query()->count())->toBe(2);
    expect(
      Designation::query()
        ->where('title', 'Head Prefect')
        ->first()->eligibleClasses
    )->toHaveCount(2);
    assertDatabaseHas('designations', [
      'institution_id' => $this->institution->id,
      'title' => 'Examination Officer',
      'is_for_students' => false
    ]);
  }
);

it('keeps designation titles unique within each institution', function () {
  $otherInstitution = Institution::factory()->create();
  $this->actingAs($this->admin)
    ->post(
      $this->route('designations.store'),
      designationPayload([
        'eligible_class_ids' => [$this->classA->id]
      ])
    )
    ->assertOk();
  $this->actingAs($this->admin)
    ->post(
      $this->route('designations.store'),
      designationPayload([
        'eligible_class_ids' => [$this->classA->id]
      ])
    )
    ->assertSessionHasErrors('title');

  $otherClass = Classification::factory()
    ->withInstitution($otherInstitution)
    ->create();
  actingAs($otherInstitution->createdBy)
    ->post(
      route('institutions.designations.store', $otherInstitution->uuid),
      designationPayload([
        'eligible_class_ids' => [$otherClass->id]
      ])
    )
    ->assertOk();
});

it(
  'requires student eligible classes and rejects classes or class scope for staff designations',
  function () {
    actingAs($this->admin)
      ->post($this->route('designations.store'), designationPayload())
      ->assertSessionHasErrors('eligible_class_ids');
    actingAs($this->admin)
      ->post(
        $this->route('designations.store'),
        designationPayload([
          'is_for_students' => false,
          'scope' => 'classification',
          'eligible_class_ids' => [$this->classA->id]
        ])
      )
      ->assertSessionHasErrors('scope');
    actingAs($this->admin)
      ->post(
        $this->route('designations.store'),
        designationPayload([
          'is_for_students' => false,
          'eligible_class_ids' => [$this->classA->id]
        ])
      )
      ->assertSessionHasErrors('eligible_class_ids');
  }
);

it('rejects eligible classes from another institution', function () {
  $otherInstitution = Institution::factory()->create();
  $otherClass = Classification::factory()
    ->withInstitution($otherInstitution)
    ->create();
  actingAs($this->admin)
    ->post(
      $this->route('designations.store'),
      designationPayload([
        'eligible_class_ids' => [$otherClass->id]
      ])
    )
    ->assertSessionHasErrors('eligible_class_ids');
});

it(
  'lets teachers manage student designations while staff designations remain administrator-only',
  function () {
    $designation = Designation::factory()->create([
      'institution_id' => $this->institution->id,
      'is_for_students' => false
    ]);
    $teacher = User::factory()
      ->teacher($this->institution)
      ->create();
    $studentUser = User::factory()
      ->student($this->institution)
      ->create();
    actingAs($teacher)
      ->get($this->route('designations.index'))
      ->assertOk();
    actingAs($teacher)
      ->get($this->route('designations.show', [$designation]))
      ->assertOk();
    actingAs($studentUser)
      ->get($this->route('designations.index'))
      ->assertForbidden();
    actingAs($teacher)
      ->post(
        $this->route('designations.store'),
        designationPayload([
          'eligible_class_ids' => [$this->classA->id]
        ])
      )
      ->assertOk();
    actingAs($teacher)
      ->post(
        $this->route('designations.store'),
        designationPayload([
          'title' => 'Staff Officer',
          'is_for_students' => false,
          'eligible_class_ids' => []
        ])
      )
      ->assertForbidden();

    actingAs($this->admin)
      ->get($this->route('designations.index'))
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/designations/list-designations')
          ->has('designations.data')
      );
    actingAs($this->admin)
      ->get($this->route('designations.show', [$designation]))
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/designations/show-designation')
          ->where('designation.id', $designation->id)
      );
    actingAs($this->admin)
      ->get($this->route('designation-assignments.index'))
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/designations/list-designation-assignments')
          ->has('assignments.data')
      );
  }
);

it(
  'assigns only eligible active students and stores the class snapshot',
  function () {
    $designation = Designation::factory()->create([
      'institution_id' => $this->institution->id
    ]);
    $designation
      ->eligibleClasses()
      ->attach($this->classA->id, ['institution_id' => $this->institution->id]);
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classA)
      ->create();
    $otherStudent = Student::factory()
      ->withInstitution($this->institution, $this->classB)
      ->create();
    $url = $this->route('designation-assignments.store');
    $payload = [
      'designation_id' => $designation->id,
      'institution_user_id' => $student->institution_user_id,
      'academic_session_id' => $this->session->id
    ];

    actingAs($this->admin)
      ->post($url, $payload)
      ->assertOk();
    $assignment = DesignationAssignment::query()->firstOrFail();
    expect($assignment->classification_id)->toBe($this->classA->id);
    $student->update(['classification_id' => $this->classB->id]);
    expect($assignment->fresh()->classification_id)->toBe($this->classA->id);

    actingAs($this->admin)
      ->post($url, [
        ...$payload,
        'institution_user_id' => $otherStudent->institution_user_id
      ])
      ->assertSessionHasErrors('institution_user_id');
  }
);

it(
  'rejects cross-institution people, staff for student designations, and students for staff designations',
  function () {
    $studentDesignation = Designation::factory()->create([
      'institution_id' => $this->institution->id
    ]);
    $studentDesignation
      ->eligibleClasses()
      ->attach($this->classA->id, ['institution_id' => $this->institution->id]);
    $staff = User::factory()
      ->teacher($this->institution)
      ->create()
      ->institutionUsers()
      ->first();
    $foreignInstitution = Institution::factory()->create();
    $foreignClass = Classification::factory()
      ->withInstitution($foreignInstitution)
      ->create();
    $foreignStudent = Student::factory()
      ->withInstitution($foreignInstitution, $foreignClass)
      ->create();
    $url = $this->route('designation-assignments.store');
    $base = [
      'designation_id' => $studentDesignation->id,
      'academic_session_id' => $this->session->id
    ];

    actingAs($this->admin)
      ->post($url, [...$base, 'institution_user_id' => $staff->id])
      ->assertSessionHasErrors('institution_user_id');
    actingAs($this->admin)
      ->post($url, [
        ...$base,
        'institution_user_id' => $foreignStudent->institution_user_id
      ])
      ->assertSessionHasErrors('institution_user_id');

    $staffDesignation = Designation::factory()->create([
      'institution_id' => $this->institution->id,
      'is_for_students' => false
    ]);
    actingAs($this->admin)
      ->post($url, [
        'designation_id' => $staffDesignation->id,
        'institution_user_id' => $foreignStudent->institution_user_id,
        'academic_session_id' => $this->session->id
      ])
      ->assertSessionHasErrors('institution_user_id');
  }
);

it(
  'allows active staff and excludes non-staff memberships from staff targets',
  function () {
    $designation = Designation::factory()->create([
      'institution_id' => $this->institution->id,
      'is_for_students' => false
    ]);
    $staff = User::factory()
      ->teacher($this->institution)
      ->create()
      ->institutionUsers()
      ->first();
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classA)
      ->create();
    $targetUrl =
      $this->route('designation-assignments.targets', []) .
      '?designation_id=' .
      $designation->id .
      '&academic_session_id=' .
      $this->session->id;
    actingAs($this->admin)
      ->getJson($targetUrl)
      ->assertOk()
      ->assertJsonCount(1, 'people')
      ->assertJsonPath('people.0.id', $staff->id);
    $teacher = User::factory()
      ->teacher($this->institution)
      ->create();
    actingAs($teacher)
      ->post($this->route('designation-assignments.store'), [
        'designation_id' => $designation->id,
        'institution_user_id' => $staff->id,
        'academic_session_id' => $this->session->id
      ])
      ->assertOk();
    expect($student)->not->toBeNull();
  }
);

it(
  'enforces active status, duplicate assignment rules, limits by scope and academic session',
  function () {
    $designation = Designation::factory()->create([
      'institution_id' => $this->institution->id,
      'max_holders' => 1
    ]);
    $designation->eligibleClasses()->attach([
      $this->classA->id => ['institution_id' => $this->institution->id],
      $this->classB->id => ['institution_id' => $this->institution->id]
    ]);
    $studentA = Student::factory()
      ->withInstitution($this->institution, $this->classA)
      ->create();
    $studentB = Student::factory()
      ->withInstitution($this->institution, $this->classB)
      ->create();
    $payload = fn($person, $session = null) => [
      'designation_id' => $designation->id,
      'institution_user_id' => $person->institution_user_id,
      'academic_session_id' => $session?->id ?? $this->session->id
    ];
    $url = $this->route('designation-assignments.store');
    actingAs($this->admin)
      ->post($url, $payload($studentA))
      ->assertOk();
    actingAs($this->admin)
      ->post($url, $payload($studentA))
      ->assertSessionHasErrors('institution_user_id');
    $secondDesignation = Designation::factory()->create([
      'institution_id' => $this->institution->id
    ]);
    $secondDesignation
      ->eligibleClasses()
      ->attach($this->classA->id, ['institution_id' => $this->institution->id]);
    actingAs($this->admin)
      ->post($url, [
        ...$payload($studentA),
        'designation_id' => $secondDesignation->id
      ])
      ->assertOk();
    actingAs($this->admin)
      ->post($url, $payload($studentB))
      ->assertSessionHasErrors('institution_user_id');
    actingAs($this->admin)
      ->post($url, $payload($studentB, AcademicSession::factory()->create()))
      ->assertOk();

    $classScoped = Designation::factory()->create([
      'institution_id' => $this->institution->id,
      'scope' => 'classification',
      'max_holders' => 1
    ]);
    $classScoped->eligibleClasses()->attach([
      $this->classA->id => ['institution_id' => $this->institution->id],
      $this->classB->id => ['institution_id' => $this->institution->id]
    ]);
    actingAs($this->admin)
      ->post($url, [
        ...$payload($studentA),
        'designation_id' => $classScoped->id
      ])
      ->assertOk();
    actingAs($this->admin)
      ->post($url, [
        ...$payload($studentB),
        'designation_id' => $classScoped->id
      ])
      ->assertOk();

    $inactive = Designation::factory()->create([
      'institution_id' => $this->institution->id,
      'is_active' => false
    ]);
    $inactive
      ->eligibleClasses()
      ->attach($this->classA->id, ['institution_id' => $this->institution->id]);
    actingAs($this->admin)
      ->post($url, [...$payload($studentA), 'designation_id' => $inactive->id])
      ->assertSessionHasErrors('designation_id');
  }
);

it(
  'ends an assignment without deleting history and prevents hard deleting a designation with history',
  function () {
    $designation = Designation::factory()->create([
      'institution_id' => $this->institution->id
    ]);
    $designation
      ->eligibleClasses()
      ->attach($this->classA->id, ['institution_id' => $this->institution->id]);
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classA)
      ->create();
    actingAs($this->admin)
      ->post($this->route('designation-assignments.store'), [
        'designation_id' => $designation->id,
        'institution_user_id' => $student->institution_user_id,
        'academic_session_id' => $this->session->id
      ])
      ->assertOk();
    $assignment = DesignationAssignment::query()->firstOrFail();
    actingAs($this->admin)
      ->post($this->route('designation-assignments.end', [$assignment]))
      ->assertOk();
    expect($assignment->fresh()->ended_at)->not->toBeNull();
    expect(
      DesignationAssignment::query()
        ->whereKey($assignment->id)
        ->exists()
    )->toBeTrue();
    $designation->delete();
    expect($designation->fresh())->not->toBeNull();
    expect(fn() => $designation->forceDelete())->toThrow(QueryException::class);
  }
);

it(
  'rejects a lower holder limit than active assignments and serves scoped assignment targets',
  function () {
    $designation = Designation::factory()->create([
      'institution_id' => $this->institution->id,
      'max_holders' => 2
    ]);
    $designation
      ->eligibleClasses()
      ->attach($this->classA->id, ['institution_id' => $this->institution->id]);
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classA)
      ->create();
    actingAs($this->admin)
      ->post($this->route('designation-assignments.store'), [
        'designation_id' => $designation->id,
        'institution_user_id' => $student->institution_user_id,
        'academic_session_id' => $this->session->id
      ])
      ->assertOk();
    $secondStudent = Student::factory()
      ->withInstitution($this->institution, $this->classA)
      ->create();
    actingAs($this->admin)
      ->post($this->route('designation-assignments.store'), [
        'designation_id' => $designation->id,
        'institution_user_id' => $secondStudent->institution_user_id,
        'academic_session_id' => $this->session->id
      ])
      ->assertOk();
    actingAs($this->admin)
      ->put(
        $this->route('designations.update', [$designation]),
        designationPayload([
          'title' => $designation->title,
          'max_holders' => 1,
          'eligible_class_ids' => [$this->classA->id]
        ])
      )
      ->assertSessionHasErrors('max_holders');
    actingAs($this->admin)
      ->getJson(
        $this->route('designation-assignments.targets') .
          '?designation_id=' .
          $designation->id .
          '&academic_session_id=' .
          $this->session->id
      )
      ->assertOk()
      ->assertJsonCount(0, 'people')
      ->assertJsonPath('current_count', 2);
  }
);
