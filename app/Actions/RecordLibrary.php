<?php

namespace App\Actions;

use App\Enums\LibrarySourceType;
use App\Enums\Media\MediaVisibility;
use App\Enums\S3Folder;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Library;
use App\Models\Media;
use App\Support\Media\MediaManager;
use App\Support\SettingsHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecordLibrary
{
    public function __construct(
        private Institution $institution,
        private InstitutionUser $institutionUser,
        private array $data
    ) {}

    public function create(): Library
    {
        return DB::transaction(function () {
            $settingsHandler = SettingsHandler::makeFromRoute();
            $classificationIds = $this->classificationIds();

            $library = $this->institution->libraries()->create([
                ...collect($this->data)
                    ->except(['classification_ids', 'file'])
                    ->toArray(),
                'institution_user_id' => $this->institutionUser->id,
                'academic_session_id' => $settingsHandler->getCurrentAcademicSession(),
                'term' => $settingsHandler->getCurrentTerm(),
                'is_public' => count($classificationIds) === 0,
                'published_at' => ($this->data['is_published'] ?? true) ? now() : null,
            ]);

            $this->syncClasses($library);
            $this->storeFile($library);

            return $library->fresh('classifications', 'media');
        });
    }

    public function update(Library $library): Library
    {
        return DB::transaction(function () use ($library) {
            $classificationIds = $this->classificationIds();
            $wasPublished = (bool) $library->published_at;
            $isPublished = (bool) ($this->data['is_published'] ?? true);

            $library->fill([
                ...collect($this->data)
                    ->except(['classification_ids', 'file'])
                    ->toArray(),
                'is_public' => count($classificationIds) === 0,
                'published_at' => $isPublished
                  ? ($wasPublished ? $library->published_at : now())
                  : null,
            ])->save();

            $this->syncClasses($library);

            if ($library->source_type === LibrarySourceType::External) {
                $this->deleteResourceMedia($library);
                $library
                    ->forceFill([
                        'file_url' => null,
                        'file_path' => null,
                        'file_name' => null,
                        'file_mime_type' => null,
                        'file_extension' => null,
                        'file_size' => null,
                    ])
                    ->save();
            }

            $this->storeFile($library);

            return $library->fresh('classifications', 'media');
        });
    }

    private function syncClasses(Library $library): void
    {
        $library
            ->classifications()
            ->syncWithPivotValues($this->classificationIds(), [
                'institution_id' => $this->institution->id,
            ]);
    }

    private function storeFile(Library $library): void
    {
        if (
            $library->source_type !== LibrarySourceType::Upload ||
            empty($this->data['file'])
        ) {
            return;
        }

        $this->deleteResourceMedia($library);

        $media = app(MediaManager::class)->storeUploadedFile(
            $this->data['file'],
            $library,
            'resource',
            $this->institution->folder(S3Folder::Library, (string) $library->id),
            $this->institution,
            currentUser(),
            visibility: MediaVisibility::Public,
            legacyUrlColumn: 'file_url',
            legacyPathColumn: 'file_path'
        );

        $library
            ->forceFill([
                'external_url' => null,
                'file_name' => $media->original_name ?: $media->filename,
                'file_mime_type' => $media->mime_type,
                'file_extension' => $media->extension,
                'file_size' => $media->size,
            ])
            ->save();
    }

    private function deleteResourceMedia(Library $library): void
    {
        $library
            ->media()
            ->where('collection_name', 'resource')
            ->get()
            ->each(function (Media $media) {
                Storage::disk($media->disk)->delete($media->path);
                $media->delete();
            });
    }

    private function classificationIds(): array
    {
        return collect($this->data['classification_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
