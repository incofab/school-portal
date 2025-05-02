<?php
namespace App\Actions\Users;

use App\Models\Association;
use App\Models\Institution;
use App\Models\UserAssociation;
use Illuminate\Support\Collection;

class RecordUserAssociation
{
  function __construct(
    private Institution $institution,
    private Association $association
  ) {
  }

  /**
   * @param int[] $users
   */
  public function run(array|Collection $institutionUserIds)
  {
    foreach ($institutionUserIds as $key => $institutionUserId) {
      UserAssociation::query()->firstOrCreate([
        'institution_user_id' => $institutionUserId,
        'association_id' => $this->association->id,
        'institution_id' => $this->institution->id
      ]);
    }
  }
}
