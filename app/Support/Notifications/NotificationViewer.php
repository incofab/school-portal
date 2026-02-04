<?php

namespace App\Support\Notifications;

use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Support\MorphMap;

class NotificationViewer
{
  public function __construct(
    public string $readerType,
    public int $readerId,
    public array $targets,
    public ?int $institutionId = null
  ) {
  }

  public static function fromRequest(): ?self
  {
    return self::make(currentUser(), currentInstitutionUser());
  }

  public static function make(
    ?User $user,
    ?InstitutionUser $institutionUser
  ): ?self {
    if (!$user && !$institutionUser) {
      return null;
    }

    $targets = [];

    if ($user) {
      self::addTarget($targets, $user->getMorphClass(), $user->id);
    }

    if ($institutionUser) {
      $institutionUser->loadMissing('student.classification');
      self::addTarget(
        $targets,
        $institutionUser->getMorphClass(),
        $institutionUser->id
      );

      $student = $institutionUser->student;
      if ($student) {
        self::addTarget($targets, $student->getMorphClass(), $student->id);

        if ($student->classification_id) {
          self::addTarget(
            $targets,
            MorphMap::key(Classification::class),
            $student->classification_id
          );
        }

        if ($student->classification?->classification_group_id) {
          self::addTarget(
            $targets,
            MorphMap::key(ClassificationGroup::class),
            $student->classification->classification_group_id
          );
        }
      }
    }

    $readerType = $institutionUser
      ? MorphMap::key(InstitutionUser::class)
      : MorphMap::key(User::class);
    $readerId = $institutionUser?->id ?? $user?->id;

    if (!$readerType || !$readerId) {
      return null;
    }

    return new self(
      $readerType,
      $readerId,
      array_values($targets),
      currentInstitution()?->id
    );
  }

  private static function addTarget(
    array &$targets,
    ?string $type,
    ?int $id
  ): void {
    if (!$type || !$id) {
      return;
    }

    $targets["{$type}:{$id}"] = [$type, $id];
  }
}
