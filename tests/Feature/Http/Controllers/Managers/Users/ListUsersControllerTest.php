<?php

use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->adminManager = User::factory()
        ->adminManager()
        ->create();
    $this->partnerManager = User::factory()
        ->partnerManager()
        ->create();
});

it('lists users for admin managers', function () {
    $user = User::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.test',
    ]);

    actingAs($this->adminManager)
        ->getJson(route('managers.users.index', ['search' => 'ada@example.test']))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('users/list-users')
                ->has('users.data', 1)
                ->where('users.data.0.id', $user->id)
                ->where('users.data.0.email', 'ada@example.test')
        );
});

it('filters users by institution role and status', function () {
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();
    $target = User::factory()->create(['first_name' => 'Target']);
    $wrongStatus = User::factory()->create(['first_name' => 'WrongStatus']);
    $wrongInstitution = User::factory()->create(['first_name' => 'WrongInst']);

    InstitutionUser::factory()->create([
        'institution_id' => $institution->id,
        'user_id' => $target->id,
        'role' => InstitutionUserType::Teacher->value,
        'status' => InstitutionUserStatus::Suspended->value,
    ]);
    InstitutionUser::factory()->create([
        'institution_id' => $institution->id,
        'user_id' => $wrongStatus->id,
        'role' => InstitutionUserType::Teacher->value,
        'status' => InstitutionUserStatus::Active->value,
    ]);
    InstitutionUser::factory()->create([
        'institution_id' => $otherInstitution->id,
        'user_id' => $wrongInstitution->id,
        'role' => InstitutionUserType::Teacher->value,
        'status' => InstitutionUserStatus::Suspended->value,
    ]);

    actingAs($this->adminManager)
        ->getJson(
            route('managers.users.index', [
                'institution_id' => $institution->id,
                'role' => InstitutionUserType::Teacher->value,
                'status' => InstitutionUserStatus::Suspended->value,
            ])
        )
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('users/list-users')
                ->has('users.data', 1)
                ->where('users.data.0.id', $target->id)
                ->where(
                    'users.data.0.institution_users.0.institution_id',
                    $institution->id
                )
        );
});

it('prevents partner managers from listing users', function () {
    actingAs($this->partnerManager)
        ->getJson(route('managers.users.index'))
        ->assertForbidden();
});

it('allows admin managers to impersonate any user from the list', function () {
    $user = User::factory()->teacher()->create();

    actingAs($this->adminManager)
        ->get(route('users.impersonate', [$user]))
        ->assertRedirect(route('user.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect(session('impersonator_id'))->toBe($this->adminManager->id);
    expect(session('impersonator_type'))->toBe('manager');
});
