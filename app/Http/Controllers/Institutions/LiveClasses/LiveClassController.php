<?php

namespace App\Http\Controllers\Institutions\LiveClasses;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\ClassDivision;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\LiveClass;
use App\Support\MorphMap;
use Illuminate\Http\Request;

class LiveClassController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->except('index');
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher,
      InstitutionUserType::Student,
      InstitutionUserType::Alumni
    ])->only('index');
  }

  /**
   * @return \Illuminate\Database\Eloquent\Builder<LiveClass>
   */
  private function getLiveClassesQuery()
  {
    $institutionUser = currentInstitutionUser();
    if (!$institutionUser?->isStudent() && !$institutionUser->isAlumni()) {
      return LiveClass::query();
    }
    $student = $institutionUser->student->load(
      'classification.classificationGroup',
      'classification.classDivisions'
    );
    $classificationId = $student->classification_id;
    $classificationGroupId = $student->classification?->classification_group_id;
    $classDivisionIds =
      $student->classification?->classDivisions?->pluck('id')->toArray() ?? [];

    return LiveClass::query()
      ->active()
      ->where(function ($q) use (
        $classificationId,
        $classificationGroupId,
        $classDivisionIds
      ) {
        $q->where(function ($query) use ($classificationId) {
          $query
            ->where('liveable_type', MorphMap::key(Classification::class))
            ->where('liveable_id', $classificationId);
        })
          ->when($classificationGroupId, function ($query) use (
            $classificationGroupId
          ) {
            $query->orWhere(function ($innerQuery) use (
              $classificationGroupId
            ) {
              $innerQuery
                ->where(
                  'liveable_type',
                  MorphMap::key(ClassificationGroup::class)
                )
                ->where('liveable_id', $classificationGroupId);
            });
          })
          ->when(!empty($classDivisionIds), function ($query) use (
            $classDivisionIds
          ) {
            $query->orWhere(function ($innerQuery) use ($classDivisionIds) {
              $innerQuery
                ->where('liveable_type', MorphMap::key(ClassDivision::class))
                ->whereIn('liveable_id', $classDivisionIds);
            });
          });
      });
  }

  public function index(Institution $institution)
  {
    return inertia('institutions/live-classes/list-live-classes', [
      'liveClasses' => $this->getLiveClassesQuery()
        ->with('teacher', 'liveable')
        ->latest('live_classes.id')
        ->get()
    ]);
  }

  public function create(Institution $institution)
  {
    return inertia('institutions/live-classes/create-edit-live-class');
  }

  public function store(Institution $institution, Request $request)
  {
    $data = $request->validate(LiveClass::createRule());
    $liveable = $this->resolveLiveable(
      $institution,
      $data['liveable_type'],
      $data['liveable_id']
    );

    $liveClass = LiveClass::query()->create([
      'institution_id' => $institution->id,
      'teacher_user_id' => currentUser()->id,
      'title' => $data['title'],
      'meet_url' => $data['meet_url'],
      'liveable_type' => $liveable->getMorphClass(),
      'liveable_id' => $liveable->id,
      'starts_at' => $data['starts_at'] ?? null,
      'ends_at' => $data['ends_at'] ?? null,
      'is_active' => $data['is_active'] ?? true
    ]);

    return $this->ok(['liveClass' => $liveClass]);
  }

  public function edit(Institution $institution, LiveClass $liveClass)
  {
    return inertia('institutions/live-classes/create-edit-live-class', [
      'liveClass' => $liveClass
    ]);
  }

  public function update(
    Institution $institution,
    LiveClass $liveClass,
    Request $request
  ) {
    $data = $request->validate(LiveClass::createRule());
    $liveable = $this->resolveLiveable(
      $institution,
      $data['liveable_type'],
      $data['liveable_id']
    );

    $liveClass->update([
      'title' => $data['title'],
      'meet_url' => $data['meet_url'],
      'liveable_type' => $liveable->getMorphClass(),
      'liveable_id' => $liveable->id,
      'starts_at' => $data['starts_at'] ?? null,
      'ends_at' => $data['ends_at'] ?? null,
      'is_active' => $data['is_active'] ?? $liveClass->is_active
    ]);

    return $this->ok();
  }

  public function destroy(Institution $institution, LiveClass $liveClass)
  {
    $liveClass->delete();
    return $this->ok();
  }

  private function resolveLiveable(
    Institution $institution,
    string $type,
    int $id
  ): Classification|ClassDivision|ClassificationGroup {
    $query = null;
    if ($type === Classification::class) {
      $query = Classification::query();
    } elseif ($type === ClassificationGroup::class) {
      $query = ClassificationGroup::query();
    } elseif ($type === ClassDivision::class) {
      $query = ClassDivision::query();
    }

    abort_if(!$query, 422, 'Invalid class type supplied');
    $liveable = $query->where('id', $id)->first();
    abort_unless($liveable, 422, 'Class target does not belong to institution');
    return $liveable;
  }
}
