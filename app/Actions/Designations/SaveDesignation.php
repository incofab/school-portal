<?php

namespace App\Actions\Designations;

use App\Enums\DesignationScope;
use App\Models\Classification;
use App\Models\Designation;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveDesignation
{
  public function execute(
    Institution $institution,
    array $data,
    ?Designation $designation = null
  ): Designation {
    $isForStudents = filter_var(
      $data['is_for_students'] ?? false,
      FILTER_VALIDATE_BOOLEAN
    );
    $scope = $data['scope'] ?? DesignationScope::Institution->value;
    $classIds = array_values(array_unique($data['eligible_class_ids'] ?? []));

    Validator::make($data, [
      'title' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'is_for_students' => ['required', 'boolean'],
      'scope' => ['required', 'in:institution,classification'],
      'max_holders' => ['required', 'integer', 'min:1'],
      'is_active' => ['required', 'boolean'],
      'eligible_class_ids' => $isForStudents
        ? ['required', 'array', 'min:1']
        : ($scope === DesignationScope::Classification->value
          ? ['nullable', 'array']
          : ['nullable', 'array', 'max:0']),
      'eligible_class_ids.*' => ['integer', 'distinct']
    ])->validate();

    if (!$isForStudents && $scope === DesignationScope::Classification->value) {
      throw ValidationException::withMessages([
        'scope' => 'Staff designations must use institution scope.'
      ]);
    }

    if (
      $designation?->exists &&
      (int) $designation->institution_id !== (int) $institution->id
    ) {
      throw ValidationException::withMessages([
        'designation' =>
          'This designation does not belong to the current institution.'
      ]);
    }

    $classes = Classification::query()
      ->where('institution_id', $institution->id)
      ->whereIn('id', $classIds)
      ->get();
    if ($classes->count() !== count($classIds)) {
      throw ValidationException::withMessages([
        'eligible_class_ids' => 'Choose eligible classes from this institution.'
      ]);
    }

    return DB::transaction(function () use (
      $institution,
      $data,
      $designation,
      $classIds,
      $isForStudents,
      $scope
    ) {
      $designation ??= new Designation();
      if ($designation->exists) {
        $designation
          ->newQuery()
          ->whereKey($designation->id)
          ->lockForUpdate()
          ->first();
        if (
          $designation->is_for_students !== $isForStudents &&
          $designation->assignments()->exists()
        ) {
          throw ValidationException::withMessages([
            'is_for_students' =>
              'The designation type cannot change after assignment history has been recorded.'
          ]);
        }
        $this->ensureHolderLimit(
          $designation,
          (int) $data['max_holders'],
          $scope
        );
      }

      $designation
        ->fill([
          'institution_id' => $institution->id,
          'title' => $data['title'],
          'description' => $data['description'] ?? null,
          'is_for_students' => $isForStudents,
          'scope' => $scope,
          'max_holders' => $data['max_holders'],
          'is_active' => $data['is_active']
        ])
        ->save();

      $sync = [];
      foreach ($isForStudents ? $classIds : [] as $classId) {
        $sync[$classId] = ['institution_id' => $institution->id];
      }
      $designation->eligibleClasses()->sync($sync);

      return $designation->load('eligibleClasses');
    });
  }

  private function ensureHolderLimit(
    Designation $designation,
    int $maxHolders,
    string $scope
  ): void {
    $counts = $designation
      ->activeAssignments()
      ->selectRaw(
        'academic_session_id, classification_id, count(*) as aggregate'
      )
      ->groupBy('academic_session_id', 'classification_id')
      ->get();

    foreach ($counts->groupBy('academic_session_id') as $sessionCounts) {
      $maxCount =
        $scope === DesignationScope::Classification->value
          ? $sessionCounts->max('aggregate')
          : $sessionCounts->sum('aggregate');
      if ($maxCount > $maxHolders) {
        throw ValidationException::withMessages([
          'max_holders' => "The limit cannot be below {$maxCount} active assignment(s) in an academic session."
        ]);
      }
    }
  }
}
