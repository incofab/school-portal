<?php

use App\Enums\Gender;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('shows the profile page to global admin managers', function () {
  $user = User::factory()
    ->adminManager()
    ->create();

  actingAs($user)
    ->get(route('users.profile'))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('users/profile')
        ->where('user.id', $user->id)
        ->has('user.roles')
    );
});

it('shows the profile page to partner managers', function () {
  $user = User::factory()
    ->partnerManager()
    ->create();

  actingAs($user)
    ->get(route('users.profile'))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('users/profile')
        ->where('user.id', $user->id)
        ->has('user.roles')
    );
});

it('shows the profile page to institution users', function () {
  $institution = Institution::factory()->create();
  $user = User::factory()
    ->teacher($institution)
    ->create();

  actingAs($user)
    ->get(route('users.profile'))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('users/profile')
        ->where('user.id', $user->id)
        ->has('user.institution_users', 1)
    );
});

it('updates the authenticated users basic profile fields', function () {
  $user = User::factory()->create([
    'first_name' => 'Old',
    'last_name' => 'Name',
    'email' => 'old@example.test'
  ]);

  actingAs($user)
    ->putJson(route('users.profile.update'), [
      'first_name' => 'Ada',
      'last_name' => 'Lovelace',
      'other_names' => 'Byron',
      'email' => 'ada@example.test',
      'phone' => '08012345678',
      'gender' => Gender::Female->value
    ])
    ->assertOk()
    ->assertJsonPath('message', 'Profile updated successfully');

  $this->assertDatabaseHas('users', [
    'id' => $user->id,
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
    'other_names' => 'Byron',
    'email' => 'ada@example.test',
    'phone' => '08012345678',
    'gender' => Gender::Female->value
  ]);
});

it('does not allow a user to take another users email address', function () {
  $user = User::factory()->create(['email' => 'owner@example.test']);
  User::factory()->create(['email' => 'taken@example.test']);

  actingAs($user)
    ->putJson(route('users.profile.update'), [
      'first_name' => 'Owner',
      'last_name' => 'User',
      'other_names' => '',
      'email' => 'taken@example.test',
      'phone' => '08012345678',
      'gender' => Gender::Male->value
    ])
    ->assertUnprocessable()
    ->assertJsonValidationErrors(['email']);
});

it('uploads the authenticated users profile photo', function () {
  Storage::fake('s3_public');
  $user = User::factory()->create();

  $response = actingAs($user)->postJson(route('users.profile.upload-photo'), [
    'photo' => UploadedFile::fake()->image('profile.jpg', 300, 300)
  ]);

  $response->assertOk()->assertJsonStructure(['url']);

  $user->refresh();

  expect($user->photo)->not->toBeNull();
  $this->assertDatabaseHas('media', [
    'mediable_type' => $user->getMorphClass(),
    'mediable_id' => $user->id,
    'collection_name' => 'profile_photo',
    'disk' => 's3_public'
  ]);
});
