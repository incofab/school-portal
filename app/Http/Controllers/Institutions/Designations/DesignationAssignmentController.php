<?php

namespace App\Http\Controllers\Institutions\Designations;

use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Designation;
use App\Models\DesignationAssignment;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DesignationAssignmentController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(Institution $institution, Request $request)
  {
    $query = DesignationAssignment::query()->with([
      'designation',
      'institutionUser.user',
      'institutionUser.student',
      'classification',
      'academicSession'
    ]);
    $query->when(
      $request->filled('academic_session_id'),
      fn($q) => $q->where(
        'academic_session_id',
        $request->integer('academic_session_id')
      )
    );
    $query->when(
      $request->filled('designation_id'),
      fn($q) => $q->where('designation_id', $request->integer('designation_id'))
    );
    $query->when(
      $request->filled('classification_id'),
      fn($q) => $q->where(
        'classification_id',
        $request->integer('classification_id')
      )
    );
    $query->when(
      $request->filled('status'),
      fn($q) => $request->status === 'active'
        ? $q->whereNull('ended_at')
        : ($request->status === 'history'
          ? $q->whereNotNull('ended_at')
          : null)
    );
    $query->when(
      $request->filled('person'),
      fn($q) => $q->whereHas(
        'institutionUser.user',
        fn($u) => $u->where(function ($nameQuery) use ($request) {
          $search = '%' . $request->string('person') . '%';
          $nameQuery
            ->where('first_name', 'like', $search)
            ->orWhere('last_name', 'like', $search)
            ->orWhere('other_names', 'like', $search);
        })
      )
    );

    return Inertia::render(
      'institutions/designations/list-designation-assignments',
      [
        'assignments' => $query
          ->latest('assigned_at')
          ->paginate(25)
          ->withQueryString(),
        'designations' => Designation::query()
          ->orderBy('title')
          ->get(['id', 'title']),
        'classifications' => Classification::query()
          ->orderBy('title')
          ->get(['id', 'title']),
        'academicSessions' => AcademicSession::query()
          ->orderByDesc('id')
          ->get(['id', 'title'])
      ]
    );
  }

  public function targets(Institution $institution, Request $request)
  {
    $data = $request->validate([
      'designation_id' => ['required', 'integer', 'exists:designations,id'],
      'academic_session_id' => [
        'required',
        'integer',
        'exists:academic_sessions,id'
      ]
    ]);
    $designation = Designation::query()
      ->whereKey($data['designation_id'])
      ->firstOrFail();
    abort_unless(
      (int) $designation->institution_id === (int) $institution->id,
      404
    );
    abort_unless($designation->is_active, 422, 'This designation is inactive.');

    $activeAssignments = $designation
      ->activeAssignments()
      ->where('academic_session_id', $data['academic_session_id']);
    $count = $activeAssignments->count();
    $countsByClass =
      $designation->scope->value === 'classification'
        ? $designation
          ->activeAssignments()
          ->where('academic_session_id', $data['academic_session_id'])
          ->selectRaw('classification_id, count(*) as aggregate')
          ->groupBy('classification_id')
          ->pluck('aggregate', 'classification_id')
        : [];
    $eligibleClassIds = $designation
      ->eligibleClasses()
      ->pluck('classifications.id');
    $classesWithCapacity = $eligibleClassIds->filter(
      fn($classId) => ($countsByClass[$classId] ?? 0) <
        $designation->max_holders
    );
    $people = InstitutionUser::query()
      ->where('status', InstitutionUserStatus::Active->value)
      ->whereDoesntHave(
        'designationAssignments',
        fn($q) => $q
          ->where('designation_id', $designation->id)
          ->where('academic_session_id', $data['academic_session_id'])
          ->whereNull('ended_at')
      )
      ->when(
        $designation->scope->value === 'institution' &&
          $count >= $designation->max_holders,
        fn($q) => $q->whereRaw('1 = 0')
      )
      ->when(
        $designation->is_for_students,
        fn($q) => $q
          ->where('type', InstitutionUserType::Student->value)
          ->whereHas(
            'student',
            fn($sq) => $sq->whereIn(
              'classification_id',
              $designation->scope->value === 'classification'
                ? $classesWithCapacity
                : $eligibleClassIds
            )
          ),
        fn($q) => $q
          ->whereIn(
            'type',
            [
              InstitutionUserType::Teacher->value,
              InstitutionUserType::Accountant->value,
              InstitutionUserType::Others->value
            ]
          )
          ->whereDoesntHave('student')
      )
      ->with([
        'user:id,first_name,last_name,other_names',
        'student.classification'
      ])
      ->orderBy('id')
      ->get();

    return response()->json([
      'people' => $people,
      'holder_limit' => $designation->max_holders,
      'current_count' => $count,
      'current_counts_by_class' => $countsByClass,
      'scope' => $designation->scope->value
    ]);
  }

  public function store(
    Institution $institution,
    Request $request,
    \App\Actions\Designations\AssignDesignation $action
  ) {
    $data = $request->validate([
      'designation_id' => [
        'required',
        'integer',
        Rule::exists('designations', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'institution_user_id' => [
        'required',
        'integer',
        Rule::exists('institution_users', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'academic_session_id' => [
        'required',
        'integer',
        'exists:academic_sessions,id'
      ],
      'note' => ['nullable', 'string', 'max:2000']
    ]);
    $designation = Designation::query()
      ->whereKey($data['designation_id'])
      ->firstOrFail();
    $person = InstitutionUser::query()
      ->whereKey($data['institution_user_id'])
      ->firstOrFail();
    $session = AcademicSession::query()
      ->whereKey($data['academic_session_id'])
      ->firstOrFail();
    $action->execute(
      $institution,
      $designation,
      $person,
      $session,
      currentUser()->id,
      $data['note'] ?? null
    );
    return $this->ok(['message' => 'Designation assigned successfully.']);
  }

  public function end(
    Institution $institution,
    DesignationAssignment $designationAssignment,
    \App\Actions\Designations\EndDesignationAssignment $action
  ) {
    abort_unless(
      (int) $designationAssignment->institution_id === (int) $institution->id,
      404
    );
    $action->execute($institution, $designationAssignment);
    return $this->ok(['message' => 'Designation assignment ended.']);
  }
}
