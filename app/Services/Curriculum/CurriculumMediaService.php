<?php

namespace App\Services\Curriculum;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Media\MediaVisibility;
use App\Enums\S3Folder;
use App\Models\Institution;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\Media;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\Media\MediaManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CurriculumMediaService
{
  public const COLLECTION = 'attachments';

  public function __construct(
    private MediaManager $mediaManager,
    private AcademicActivityLogger $activityLogger
  ) {
  }

  public function storeLessonPlanAttachment(
    Institution $institution,
    LessonPlan $lessonPlan,
    UploadedFile $file
  ): Media {
    return $this->store(
      $institution,
      $lessonPlan,
      $file,
      S3Folder::LessonPlans,
      'curriculum.lesson_plan_attachment_uploaded',
      'Lesson plan attachment uploaded.',
      ['lesson_plan_id' => $lessonPlan->id]
    );
  }

  public function storeLessonNoteAttachment(
    Institution $institution,
    LessonNote $lessonNote,
    UploadedFile $file
  ): Media {
    return $this->store(
      $institution,
      $lessonNote,
      $file,
      S3Folder::LessonNotes,
      'curriculum.lesson_note_attachment_uploaded',
      'Lesson note attachment uploaded.',
      ['lesson_note_id' => $lessonNote->id]
    );
  }

  public function deleteLessonPlanAttachment(
    Institution $institution,
    LessonPlan $lessonPlan,
    Media $media
  ): void {
    $this->delete(
      $institution,
      $lessonPlan,
      $media,
      'curriculum.lesson_plan_attachment_deleted',
      'Lesson plan attachment deleted.',
      ['lesson_plan_id' => $lessonPlan->id]
    );
  }

  public function deleteLessonNoteAttachment(
    Institution $institution,
    LessonNote $lessonNote,
    Media $media
  ): void {
    $this->delete(
      $institution,
      $lessonNote,
      $media,
      'curriculum.lesson_note_attachment_deleted',
      'Lesson note attachment deleted.',
      ['lesson_note_id' => $lessonNote->id]
    );
  }

  private function store(
    Institution $institution,
    LessonPlan|LessonNote $owner,
    UploadedFile $file,
    S3Folder $folder,
    string $event,
    string $message,
    array $properties
  ): Media {
    $result = $this->mediaManager->storeUploadedFile(
      $file,
      $owner,
      self::COLLECTION,
      $institution->folder($folder, (string) $owner->id),
      $institution,
      currentUser(),
      visibility: MediaVisibility::Public
    );

    $this->activityLogger->workflowEvent(
      $institution,
      $event,
      ActivityLogCategory::Curriculum,
      'uploaded_attachment',
      $message,
      [
        ...$properties,
        'media_id' => $result->media->id,
        'original_name' => $result->media->original_name,
        'collection_name' => $result->media->collection_name,
        'mime_type' => $result->media->mime_type,
        'size' => $result->media->size
      ],
      $owner
    );

    return $result->media;
  }

  private function delete(
    Institution $institution,
    Model $owner,
    Media $media,
    string $event,
    string $message,
    array $properties
  ): void {
    abort_unless(
      $media->mediable_type === $owner->getMorphClass() &&
        $media->mediable_id === $owner->id &&
        $media->collection_name === self::COLLECTION,
      404
    );

    $payload = [
      ...$properties,
      'media_id' => $media->id,
      'original_name' => $media->original_name,
      'collection_name' => $media->collection_name,
      'mime_type' => $media->mime_type,
      'size' => $media->size
    ];

    Storage::disk($media->disk)->delete($media->path);
    $media->delete();

    $this->activityLogger->workflowEvent(
      $institution,
      $event,
      ActivityLogCategory::Curriculum,
      'deleted_attachment',
      $message,
      $payload,
      $owner
    );
  }
}
