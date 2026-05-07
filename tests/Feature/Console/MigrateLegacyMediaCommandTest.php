<?php

use App\Models\Institution;
use App\Models\Media;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;

it('backfills legacy media references into the media table', function () {
  Storage::fake('s3_public');

  $institution = Institution::factory()->create();
  $path = $institution->folder() . '/legacy-logo.jpg';
  Storage::disk('s3_public')->put($path, 'legacy-image-content');

  $url = Storage::disk('s3_public')->url($path);
  $institution->forceFill(['photo' => $url])->save();

  Artisan::call('media:migrate-legacy', [
    '--skip-disk-scan' => true
  ]);

  assertDatabaseHas('media', [
    'mediable_type' => $institution->getMorphClass(),
    'mediable_id' => $institution->id,
    'collection_name' => 'profile_photo',
    'filename' => basename($url),
    'directory' => trim(dirname($url), '/')
  ]);

  expect(Media::query()->count())->toBe(1);
});
