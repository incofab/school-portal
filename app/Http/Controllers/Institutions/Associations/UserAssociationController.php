<?php

namespace App\Http\Controllers\Institutions\Associations;

use App\Actions\Users\RecordUserAssociation;
use Inertia\Inertia;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\User;
use App\Models\UserAssociation;
use App\Rules\ValidateExistsRule;
use App\Support\MorphMap;
use App\Support\UITableFilters\UserAssociationUITableFilters;

class UserAssociationController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(
    Request $request,
    Institution $institution,
    ?Association $association = null
  ) {
    $query = UserAssociationUITableFilters::make(
      [...$request->all(), 'association' => $association?->id],
      UserAssociation::query()->select('user_associations.*')
    )
      ->filterQuery()
      ->getQuery()
      ->with('institutionUser.user');

    return Inertia::render('institutions/associations/list-user-associations', [
      'associations' => Association::all(),
      'userAssociations' => paginateFromRequest($query)
    ]);
  }

  function create(
    Institution $institution,
    ?string $morphableType = null,
    ?int $morphableId = null
  ) {
    $usersQuery = User::query()
      ->select('users.*')
      ->join('institution_users', 'users.id', 'institution_users.user_id')
      ->where('institution_users.institution_id', $institution->id);
    if ($morphableType && $morphableId) {
      switch ($morphableType) {
        case MorphMap::key(Classification::class):
          $usersQuery = User::query()
            ->join('students', 'users.id', 'students.user_id')
            ->where('students.classification_id', $morphableId);
          break;
        case MorphMap::key(ClassificationGroup::class):
          $usersQuery = User::query()
            ->join('students', 'users.id', 'students.user_id')
            ->join(
              'classifications',
              'students.classification_id',
              'classifications.id'
            )
            ->where('classifications.classification_group_id', $morphableId);
          break;
        case MorphMap::key(Institution::class):
        default:
          // $usersQuery = $usersQuery;
          break;
      }
    }
    return Inertia::render(
      'institutions/associations/create-user-association',
      [
        'associations' => Association::all(),
        'users' => $usersQuery
          ->with('institutionUser')
          ->get()
          ->filter(fn($item) => !empty($item->institutionUser))
      ]
    );
  }

  public function store(Request $request, Institution $institution)
  {
    $userIdsCheck = $institution
      ->institutionUsers()
      ->get()
      ->pluck('id')
      ->toArray();

    $associationExistRule = new ValidateExistsRule(Association::class);
    $validatedData = $request->validate([
      'association_id' => ['required', $associationExistRule],
      'institution_user_ids' => ['required', 'array', 'min:1'],
      'institution_user_ids.*' => [
        'required',
        function ($attr, $value, $fail) use ($userIdsCheck) {
          if (!in_array($value, $userIdsCheck)) {
            $fail('You can only select users that belong to this school');
            return;
          }
        }
      ]
    ]);
    (new RecordUserAssociation(
      $institution,
      $associationExistRule->getModel()
    ))->run($validatedData['institution_user_ids']);
    return $this->ok();
  }

  function destroy(Institution $institution, UserAssociation $userAssociation)
  {
    $userAssociation->delete();
    return $this->ok();
  }
}
