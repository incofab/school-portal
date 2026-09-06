<?php

use App\Enums\InstitutionPermission;
use App\Enums\InstitutionUserType;
use App\Actions\RecordStaff;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Models\User;
use App\Services\Institutions\InstitutionRoleService;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\artisan;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  // $roleService = app(InstitutionRoleService::class);
  // $roleService->ensurePermissionInventory();
  // $roleService->ensureDefaultRoles($this->institution);
  actingAs($this->admin);
});

it('provisions every built-in role with its default permissions', function () {
  $roles = app(InstitutionRoleService::class)->forInstitution(
    $this->institution
  );

  expect($roles)->toHaveCount(count(InstitutionUserType::cases()));

  $adminRole = $roles->firstWhere('name', InstitutionUserType::Admin->value);
  expect($adminRole->permissions->pluck('name'))
    ->toContain(InstitutionPermission::ManageRoles->value)
    ->toContain(InstitutionPermission::ManagePayroll->value)
    ->not->toContain('view-dashboard');
});

it(
  'supports role creation, permission assignment, editing, and deletion',
  function () {
    $permission = InstitutionPermission::ManageLibrary->value;
    $payload = [
      'name' => 'Class Assistant',
      'description' => 'Helps teachers manage class records.',
      'permissions' => [$permission]
    ];

    postJson(
      route('institutions.roles.store', $this->institution),
      $payload
    )->assertOk();

    $role = Role::query()
      ->where('institution_id', $this->institution->id)
      ->where('name', 'Class Assistant')
      ->firstOrFail();

    assertDatabaseHas('roles', [
      'id' => $role->id,
      'description' => $payload['description']
    ]);
    assertDatabaseHas('role_has_permissions', [
      'role_id' => $role->id,
      'permission_id' => \Spatie\Permission\Models\Permission::where(
        'name',
        $permission
      )->value('id')
    ]);

    putJson(route('institutions.roles.update', [$this->institution, $role]), [
      'name' => 'Senior Class Assistant',
      'description' => 'Coordinates class-record support.',
      'permissions' => [InstitutionPermission::ManageLibrary->value]
    ])->assertOk();

    expect($role->fresh()->name)->toBe('Senior Class Assistant');
    expect(
      $role
        ->fresh('permissions')
        ->permissions->pluck('name')
        ->all()
    )->toBe([InstitutionPermission::ManageLibrary->value]);

    deleteJson(
      route('institutions.roles.destroy', [$this->institution, $role])
    )->assertOk();
    expect($role->fresh())->toBeNull();
  }
);

it(
  'assigns roles to institution users without changing the institution user type',
  function () {
    $role = $this->institution
      ->roles()
      ->where('name', InstitutionUserType::Teacher->value)
      ->firstOrFail();
    $user = User::factory()->create();
    $institutionUser = InstitutionUser::factory()
      ->teacher($this->institution)
      ->create([
        'user_id' => $user->id
      ]);

    app(InstitutionRoleService::class)->assign($institutionUser, $role);

    expect($institutionUser->fresh()->role)->toBe(InstitutionUserType::Teacher);
    expect(
      $institutionUser
        ->fresh('roles')
        ->roles->first()
        ->is($role)
    )->toBeTrue();
  }
);

it(
  'automatically registers new staff as teachers and assigns the selected role',
  function () {
    $role = $this->institution
      ->roles()
      ->where('name', InstitutionUserType::Teacher->value)
      ->firstOrFail();

    $user = RecordStaff::make($this->institution, [
      'first_name' => 'Ada',
      'last_name' => 'Lovelace',
      'email' => 'ada@example.com',
      'role' => $role->id
    ])->create();

    $institutionUser = $user->institutionUsers()->firstOrFail();
    expect($institutionUser->type)->toBe(InstitutionUserType::Teacher);
    expect(
      $institutionUser
        ->fresh('roles')
        ->roles->first()
        ->is($role)
    )->toBeTrue();
  }
);

it(
  'assigns the default teacher role when no assignable role is supplied',
  function () {
    $user = RecordStaff::make($this->institution, [
      'first_name' => 'Grace',
      'last_name' => 'Hopper',
      'email' => 'grace@example.com'
    ])->create();

    $institutionUser = $user->institutionUsers()->firstOrFail();

    expect($institutionUser->type)
      ->toBe(InstitutionUserType::Teacher)
      ->and($institutionUser->fresh('roles')->roles->first()->name)
      ->toBe(InstitutionUserType::Teacher->value);
  }
);

it(
  'does not allow an enum value to be submitted as an assignable staff role',
  function () {
    postJson(route('institutions.users.store', $this->institution), [
      'first_name' => 'Invalid',
      'last_name' => 'Role',
      'email' => 'invalid-role@example.com',
      'password' => 'password',
      'password_confirmation' => 'password',
      'type' => InstitutionUserType::Teacher->value
    ])
      ->assertUnprocessable()
      ->assertJsonValidationErrors('role');
  }
);

it('filters institution members using their assigned role id', function () {
  $role = app(InstitutionRoleService::class)->create($this->institution, [
    'name' => 'Library Assistant',
    'description' => 'Supports the school library.',
    'permissions' => [InstitutionPermission::ManageLibrary->value]
  ]);

  $user = RecordStaff::make($this->institution, [
    'first_name' => 'Katherine',
    'last_name' => 'Johnson',
    'email' => 'katherine@example.com',
    'role' => $role->id
  ])->create();

  getJson(
    route('institutions.users.index', [$this->institution, 'role' => $role->id])
  )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->where('institutionUsers.data.0.user_id', $user->id)
        ->where('institutionUsers.data.0.roles.0.id', $role->id)
    );
});

it(
  'repairs a missing built-in role when the provisioning command is run',
  function () {
    $teacherRole = $this->institution
      ->roles()
      ->where('name', InstitutionUserType::Teacher->value)
      ->firstOrFail();
    $teacherRole->delete();

    artisan('institutions:seed-roles')->assertSuccessful();

    expect(
      $this->institution
        ->roles()
        ->where('name', InstitutionUserType::Teacher->value)
        ->exists()
    )->toBeTrue();
  }
);

it('assigns missing built-in roles during provisioning', function () {
  $institutionUser = InstitutionUser::factory()
    ->teacher($this->institution)
    ->create();

  artisan('institutions:seed-roles')->assertSuccessful();

  expect(
    $institutionUser
      ->fresh('roles')
      ->roles->pluck('name')
      ->all()
  )->toBe([InstitutionUserType::Teacher->value]);
});

it(
  'checks assigned permissions independently from the institution user type',
  function () {
    $institutionUser = InstitutionUser::factory()
      ->teacher($this->institution)
      ->create();
    $role = app(InstitutionRoleService::class)->create($this->institution, [
      'name' => 'Results Viewer',
      'permissions' => [InstitutionPermission::ManageChat->value]
    ]);

    app(InstitutionRoleService::class)->assign($institutionUser, $role);

    expect(
      $institutionUser
        ->fresh()
        ->hasInstitutionPermission(InstitutionPermission::ManageChat)
    )
      ->toBeTrue()
      ->and(
        $institutionUser
          ->fresh()
          ->hasInstitutionPermission(InstitutionPermission::ManageFees)
      )
      ->toBeFalse();
  }
);

it(
  'keeps the permission inventory intentionally small and domain focused',
  function () {
    $permissionNames = \Spatie\Permission\Models\Permission::query()
      ->where('guard_name', 'web')
      ->pluck('name')
      ->all();

    expect($permissionNames)->toEqualCanonicalizing(
      collect(InstitutionPermission::cases())
        ->map(fn($permission) => $permission->value)
        ->all()
    );
    expect($permissionNames)
      ->toContain(
        'manage-fees',
        'manage-payroll',
        'manage-attendance',
        'manage-chat'
      )
      ->not->toContain(
        'view-dashboard',
        'view-students',
        'manage-students',
        'view-fees',
        'view-results',
        'manage-settings',
        'record-results',
        'manage-exams',
        'manage-assignments',
        'manage-live-classes'
      );
  }
);

it('does not create permissions for natural access rules', function () {
  $institutionUser = InstitutionUser::factory()
    ->teacher($this->institution)
    ->create();
  $role = app(InstitutionRoleService::class)->create($this->institution, [
    'name' => 'Fees Manager',
    'permissions' => [InstitutionPermission::ManageFees->value]
  ]);

  app(InstitutionRoleService::class)->assign($institutionUser, $role);

  expect(
    $institutionUser
      ->fresh()
      ->hasInstitutionPermission(InstitutionPermission::ManageFees)
  )
    ->toBeTrue()
    ->and(
      $institutionUser
        ->fresh()
        ->hasInstitutionPermission(InstitutionPermission::ManageChat)
    )
    ->toBeFalse();
});

it('exposes roles and permissions to the role pages', function () {
  getJson(route('institutions.roles.index', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/roles/list-roles')
        ->has('roles.data', count(InstitutionUserType::cases()))
    );

  getJson(route('institutions.roles.create', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/roles/create-edit-role')
        ->has('permissions')
    );
});

it(
  "shares the current institution user's permissions with Inertia",
  function () {
    $institutionUser = $this->institution
      ->institutionUsers()
      ->where('user_id', $this->admin->id)
      ->firstOrFail();
    $adminRole = $this->institution
      ->roles()
      ->where('name', InstitutionUserType::Admin->value)
      ->firstOrFail();

    app(InstitutionRoleService::class)->assign($institutionUser, $adminRole);

    getJson(
      route('institutions.roles.index', $this->institution)
    )->assertInertia(
      fn(Assert $page) => $page->where(
        'shared__currentUserPermissions',
        fn($permissions) => $permissions->contains(
          InstitutionPermission::ManageRoles->value
        )
      )
    );
  }
);
