<?php

namespace App\Support\Media;

use App\Enums\Media\MediaKind;
use App\Enums\Media\MediaStatus;
use App\Enums\Media\MediaVisibility;
use App\Models\Institution;
use App\Models\Media;
use App\Models\User;
use App\Support\Res;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MediaManager
{
  public function storeUploadedFile(
    UploadedFile $file,
    ?Model $mediable,
    string $collectionName,
    string $directory,
    ?Institution $institution = null,
    ?User $uploadedBy = null,
    string $disk = 's3_public',
    MediaVisibility $visibility = MediaVisibility::Public,
    array $meta = [],
    ?string $legacyUrlColumn = null,
    ?string $legacyPathColumn = null
  ): Res {
    return $this->storeFile(
      $file,
      $file->getClientOriginalName(),
      $mediable,
      $collectionName,
      $directory,
      $institution,
      $uploadedBy,
      $disk,
      $visibility,
      $meta,
      $legacyUrlColumn,
      $legacyPathColumn
    );
  }

  public function storeLocalFile(
    string $sourcePath,
    ?Model $mediable,
    string $collectionName,
    string $directory,
    ?Institution $institution = null,
    ?User $uploadedBy = null,
    string $disk = 's3_public',
    MediaVisibility $visibility = MediaVisibility::Public,
    array $meta = [],
    ?string $legacyUrlColumn = null,
    ?string $legacyPathColumn = null
  ): Res {
    $file = new File($sourcePath);

    return $this->storeFile(
      $file,
      basename($sourcePath),
      $mediable,
      $collectionName,
      $directory,
      $institution,
      $uploadedBy,
      $disk,
      $visibility,
      $meta,
      $legacyUrlColumn,
      $legacyPathColumn
    );
  }

  private function storeFile(
    File|UploadedFile $file,
    string $originalName,
    ?Model $mediable,
    string $collectionName,
    string $directory,
    ?Institution $institution,
    ?User $uploadedBy,
    string $disk,
    MediaVisibility $visibility,
    array $meta,
    ?string $legacyUrlColumn,
    ?string $legacyPathColumn
  ): Res {
    $directory = trim($directory, '/');
    $extension =
      $file instanceof UploadedFile
        ? ($file->getClientOriginalExtension() ?:
        $file->extension())
        : $file->extension();
    $mimeType =
      $file instanceof UploadedFile
        ? ($file->getMimeType() ?:
        $file->getClientMimeType())
        : $file->getMimeType();
    $filename = $this->makeFilename($originalName, $extension);
    $path = $directory === '' ? $filename : "{$directory}/{$filename}";

    $media = Media::query()->create([
      'institution_id' => $institution?->id,
      'uploaded_by_user_id' => $uploadedBy?->id,
      'mediable_type' => $mediable?->getMorphClass(),
      'mediable_id' => $mediable?->getKey(),
      'collection_name' => $collectionName,
      'disk' => $disk,
      'directory' => $directory === '' ? null : $directory,
      'path' => $path,
      'filename' => $filename,
      'original_name' => $originalName,
      'extension' => $extension ?: null,
      'mime_type' => $mimeType,
      'size' => $file->getSize(),
      'kind' => MediaKind::fromMimeType($mimeType),
      'visibility' => $visibility,
      'status' => MediaStatus::Pending,
      'checksum_sha256' => $this->getChecksum($file),
      'meta' => $meta
    ]);

    try {
      $stream = fopen($file->getRealPath(), 'r');
      $uploaded = Storage::disk($disk)->put($path, $stream, [
        // 'visibility' => $visibility->value,
        // 'ContentType' => $mimeType
      ]);

      if (is_resource($stream)) {
        fclose($stream);
      }

      if (!$uploaded) {
        $media->delete();
        return failRes('failed to upload to media library');
      }

      $media
        ->fill([
          'status' => MediaStatus::Uploaded,
          'uploaded_at' => now(),
          'failed_at' => null,
          'failure_reason' => null
        ])
        ->save();

      $this->syncLegacyColumns(
        $mediable,
        $media,
        $legacyUrlColumn,
        $legacyPathColumn
      );

      return successRes('media uploaded successfully', [
        'media' => $media->fresh()
      ]);
    } catch (Throwable $throwable) {
      $media
        ->fill([
          'status' => MediaStatus::Failed,
          'failed_at' => now(),
          'failure_reason' => Str::limit($throwable->getMessage(), 65535, '')
        ])
        ->save();

      throw $throwable;
    }
  }

  public function registerExistingFile(
    string $path,
    ?Model $mediable,
    string $collectionName,
    ?Institution $institution = null,
    ?User $uploadedBy = null,
    string $disk = 's3_public',
    MediaVisibility $visibility = MediaVisibility::Public,
    array $meta = [],
    ?string $legacyUrlColumn = null,
    ?string $legacyPathColumn = null
  ): Media {
    $path = ltrim($path, '/');
    $mimeType = $this->safeMimeType($disk, $path);
    $size = $this->safeSize($disk, $path);
    $media = Media::query()->create([
      'institution_id' => $institution?->id,
      'uploaded_by_user_id' => $uploadedBy?->id,
      'mediable_type' => $mediable?->getMorphClass(),
      'mediable_id' => $mediable?->getKey(),
      'collection_name' => $collectionName,
      'disk' => $disk,
      'directory' =>
        Str::of($path)
          ->beforeLast('/')
          ->value() ?:
        null,
      'path' => $path,
      'filename' => basename($path),
      'original_name' => basename($path),
      'extension' => pathinfo($path, PATHINFO_EXTENSION) ?: null,
      'mime_type' => $mimeType,
      'size' => $size,
      'kind' => MediaKind::fromMimeType($mimeType),
      'visibility' => $visibility,
      'status' => MediaStatus::Uploaded,
      'uploaded_at' => now(),
      'meta' => $meta
    ]);

    $this->syncLegacyColumns(
      $mediable,
      $media,
      $legacyUrlColumn,
      $legacyPathColumn
    );

    return $media;
  }

  public function extractPathFromUrl(
    string $url,
    string $disk = 's3_public'
  ): string {
    $baseUrl = rtrim(Storage::disk($disk)->url('/'), '/');

    $relativePath = ltrim(
      (string) Str::of($url)
        ->after($baseUrl)
        ->value(),
      '/'
    );

    if ($relativePath !== '') {
      return $relativePath;
    }

    return ltrim((string) parse_url($url, PHP_URL_PATH), '/');
  }

  private function makeFilename(
    string $originalName,
    ?string $extension
  ): string {
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    $sanitized = Str::slug($basename);
    $prefix = blank($sanitized) ? 'file' : $sanitized;

    return $extension
      ? "{$prefix}-" . Str::orderedUuid()->toString() . ".{$extension}"
      : "{$prefix}-" . Str::orderedUuid()->toString();
  }

  private function getChecksum(File|UploadedFile $file): ?string
  {
    $path = $file->getRealPath();

    return $path ? hash_file('sha256', $path) : null;
  }

  private function syncLegacyColumns(
    ?Model $mediable,
    Media $media,
    ?string $legacyUrlColumn,
    ?string $legacyPathColumn
  ): void {
    if (!$mediable || (!$legacyUrlColumn && !$legacyPathColumn)) {
      return;
    }

    $updates = [];

    if ($legacyUrlColumn) {
      $updates[$legacyUrlColumn] = $media->url;
    }

    if ($legacyPathColumn) {
      $updates[$legacyPathColumn] = $media->path;
    }

    if ($updates === []) {
      return;
    }

    $mediable->forceFill($updates)->save();
  }

  private function safeMimeType(string $disk, string $path): ?string
  {
    try {
      return Storage::disk($disk)->mimeType($path);
    } catch (Throwable) {
      return null;
    }
  }

  private function safeSize(string $disk, string $path): ?int
  {
    try {
      return Storage::disk($disk)->size($path);
    } catch (Throwable) {
      return null;
    }
  }
}
