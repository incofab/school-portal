<?php

namespace App\Http\Controllers\Managers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\User;
use Inertia\Inertia;

class ListInstitutionsController extends Controller
{
  public function __invoke(?InstitutionGroup $institutionGroup = null)
  {
    $query = $this->getQuery(currentUser())->when(
      $institutionGroup,
      fn($q) => $q->where('institution_groups.id', $institutionGroup->id)
    );

    $stats = Institution::selectRaw(
      "
      SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_count,
      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
      COUNT(id) as total
    "
    )->first();
    return Inertia::render('managers/institutions/list-institutions', [
      'institutions' => paginateFromRequest(
        $query
          ->withCount('classifications')
          ->with('institutionGroup')
          ->orderByRaw(
            "institutions.status IS NOT NULL, FIELD(institutions.status, 'active', 'suspended')"
          )
          ->latest('institutions.id')
      ),
      'stats' => $stats
    ]);
  }

  private function getQuery(User $user)
  {
    $query = Institution::query()->select('institutions.*');
    if (!$user->isAdmin()) {
      $query
        ->join(
          'institution_groups',
          'institution_groups.id',
          'institutions.institution_group_id'
        )
        ->where('institution_groups.partner_user_id', $user->id);
    }
    return $query;
  }
}
