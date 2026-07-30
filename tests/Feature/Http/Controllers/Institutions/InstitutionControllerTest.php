<?php

use App\Models\Institution;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it(
  'uploads an institution logo and updates the profile photo url',
  function () {
    Storage::fake('s3_public');
    $institution = Institution::factory()->create();

    $response = actingAs($institution->createdBy)->postJson(
      route('institutions.upload-photo', $institution),
      [
        'photo' => UploadedFile::fake()->image('school-logo.jpg', 300, 300)
      ]
    );

    $response
      ->assertOk()
      ->assertJsonPath('message', 'Institution logo uploaded successfully.')
      ->assertJsonStructure(['url', 'institution' => ['photo']]);

    $institution->refresh();

    expect($institution->photo)->toBe($response->json('url'));
    expect($response->json('institution.photo'))->toBe($institution->photo);

    $media = $institution
      ->media()
      ->where('collection_name', 'profile_photo')
      ->first();

    expect($media)->not->toBeNull();
    Storage::disk('s3_public')->assertExists($media->path);

    $this->assertDatabaseHas('media', [
      'mediable_type' => $institution->getMorphClass(),
      'mediable_id' => $institution->id,
      'collection_name' => 'profile_photo',
      'disk' => 's3_public'
    ]);
  }
);

it('prevents non admins from uploading an institution logo', function () {
  Storage::fake('s3_public');
  $institution = Institution::factory()->create();
  $teacher = \App\Models\User::factory()
    ->teacher($institution)
    ->create();

  actingAs($teacher)
    ->postJson(route('institutions.upload-photo', $institution), [
      'photo' => UploadedFile::fake()->image('school-logo.jpg', 300, 300)
    ])
    ->assertForbidden();
});
