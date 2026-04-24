<?php

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

it('shows user profile to admin managers', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);
    InstitutionUser::factory()->create([
        'institution_id' => $institution->id,
        'user_id' => $user->id,
    ]);

    actingAs($this->adminManager)
        ->get(route('managers.users.show', $user))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('managers/users/show')
                ->where('userModel.id', $user->id)
                ->where('userModel.first_name', 'Ada')
                ->has('userModel.institution_users', 1)
        );
});

it('prevents partner managers from showing user profile', function () {
    $user = User::factory()->create();

    actingAs($this->partnerManager)
        ->get(route('managers.users.show', $user))
        ->assertRedirect(route('user.dashboard'));
});

it('allows admin managers to reset user password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    actingAs($this->adminManager)
        ->post(route('managers.users.reset-password', $user))
        ->assertOk();

    $user->refresh();
    expect(Hash::check(config('app.user_default_password', 'password'), $user->password))->toBeTrue();
});

it('prevents admin managers from resetting their own password via this route', function () {
    actingAs($this->adminManager)
        ->post(route('managers.users.reset-password', $this->adminManager))
        ->assertForbidden();
});

it('prevents partner managers from resetting user password', function () {
    $user = User::factory()->create();

    actingAs($this->partnerManager)
        ->post(route('managers.users.reset-password', $user))
        ->assertRedirect(route('user.dashboard'));
});
