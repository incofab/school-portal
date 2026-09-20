<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\Models\Institution;
use App\Models\InstitutionUser;

class AssistantContextResolver
{
    public function resolve(?Institution $institution = null): AssistantActorContext
    {
        $user = currentUser();
        $institutionUser = $institution && $user
            ? InstitutionUser::query()
                ->where('institution_id', $institution->id)
                ->where('user_id', $user->id)
                ->with('roles.permissions')
                ->first()
            : null;

        return new AssistantActorContext(
            user: $user,
            institution: $institution,
            institutionUser: $institutionUser,
            isGuest: $user === null,
            permissions: $institutionUser?->getAllPermissions()->pluck('name')->values()->all() ?? [],
        );
    }
}
