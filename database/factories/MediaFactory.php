<?php

namespace Database\Factories;

use App\Enums\Media\MediaKind;
use App\Enums\Media\MediaStatus;
use App\Enums\Media\MediaVisibility;
use App\Models\Institution;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $filename = fake()->uuid().'.jpg';

        return [
            'uuid' => (string) Str::orderedUuid(),
            'institution_id' => Institution::factory(),
            'uploaded_by_user_id' => User::factory(),
            'collection_name' => 'default',
            'disk' => 's3_public',
            'directory' => 'institutions/1/base',
            'path' => 'institutions/1/base/'.$filename,
            'filename' => $filename,
            'original_name' => 'sample.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'kind' => MediaKind::Image,
            'visibility' => MediaVisibility::Public,
            'status' => MediaStatus::Uploaded,
            'uploaded_at' => now(),
        ];
    }
}
