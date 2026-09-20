<?php

namespace App\DTO\AI;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;

readonly final class AssistantActorContext
{
  public function __construct(
    public ?User $user,
    public ?Institution $institution,
    public ?InstitutionUser $institutionUser,
    public bool $isGuest,
    public array $permissions = []
  ) {
  }

  public function userId(): ?int
  {
    return $this->user?->getKey();
  }

  public function institutionId(): ?int
  {
    return $this->institution?->getKey();
  }

  public function role(): ?string
  {
    return $this->institutionUser?->type?->value ??
      $this->user?->roles?->pluck('name')->first();
  }

  public function systemContext(): string
  {
    if ($this->isGuest) {
      return 'The requester is not authenticated. Only public EduManager guidance is allowed.';
    }

    $role = $this->role() ?? 'authenticated EduManager user';

    return "The requester is an authenticated {$role} in the current institution.";
  }
}
