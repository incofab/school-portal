<?php
namespace App\Actions\Fees;

use App\Models\Association;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Collection;

class FeeMembersHandler
{
  private $ids;
  function __construct(private Institution $institution)
  {
    $this->ids = [
      MorphMap::key(Classification::class) => [],
      MorphMap::key(ClassificationGroup::class) => [],
      MorphMap::key(Institution::class) => [],
      MorphMap::key(Association::class) => []
    ];
  }

  function getFeeMembers(Fee $fee, $forOwingMembers = false): Collection
  {
    $feeCategories = $fee->feeCategories;
    /** @var FeeCategory $feeCategory */
    foreach ($feeCategories as $key => $feeCategory) {
      array_push(
        $this->ids[$feeCategory->feeable_type],
        $feeCategory->feeable_id
      );
    }
    $usersQuery = $this->getMembersQuery();

    $users = $usersQuery
      ->with([
        'receipts' => fn($q) => $q->where('fee_id', $fee->id)
      ])
      ->with('student.guardian')
      ->get();

    return $forOwingMembers
      ? $users->filter(function ($item) {
        $receipt = $item->receipts->first();
        return empty($receipt) || $receipt->amount_remaining > 0;
      })
      : $users;
  }

  function getMorphMembers($feeableType, $feeableId)
  {
    if (!$feeableType || !$feeableId) {
      return [];
    }

    $this->ids[$feeableType] = [$feeableId];

    return $this->getMembersQuery()->get();
  }

  private function getMembersQuery()
  {
    $usersQuery = User::query()
      ->select('users.*')
      ->join('institution_users', 'users.id', 'institution_users.user_id')
      ->where('institution_users.institution_id', $this->institution->id);
    if (
      $this->ids[MorphMap::key(Classification::class)] ||
      $this->ids[MorphMap::key(ClassificationGroup::class)]
    ) {
      $classIds = $this->ids[MorphMap::key(Classification::class)];
      $classGroupIds = $this->ids[MorphMap::key(ClassificationGroup::class)];
      $usersQuery
        ->join('students', 'users.id', 'students.user_id')
        ->join(
          'classifications',
          'students.classification_id',
          'classifications.id'
        )
        ->where(
          fn($q) => $q
            ->whereIn('students.classification_id', $classIds)
            ->orWhereIn(
              'classifications.classification_group_id',
              $classGroupIds
            )
        );
    } elseif ($this->ids[MorphMap::key(Association::class)]) {
      $usersQuery
        ->join('users_associations', 'users.id', 'users_associations.user_id')
        ->whereIn(
          'users_associations.association_id',
          $this->ids[MorphMap::key(Association::class)]
        );
    } else {
      // Applies to all
    }
    return $usersQuery;
  }
}
