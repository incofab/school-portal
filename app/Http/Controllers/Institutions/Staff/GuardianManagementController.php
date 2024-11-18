<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\RecordGuardian;
use App\Enums\GuardianRelationship;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GuardianManagementController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  function index(Request $request, Institution $institution)
  {
    $query = GuardianStudent::query()->with('student.user', 'guardian');
    return Inertia::render('institutions/guardians/list-guardians', [
      'guardians' => paginateFromRequest($query)
    ]);
  }

  public function create(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    $students = $classification
      ->students()
      ->getQuery()
      ->with('guardian', 'user')
      ->get();
    return Inertia::render(
      'institutions/guardians/record-class-students-guardians',
      ['students' => $students, 'classification' => $classification]
    );
  }

  public function store(Request $request, Institution $institution)
  {
    $rule = [];
    foreach (request('guardians') as $id => $value) {
      $thisRule = User::generalRule(null, "guardians.$id.");
      $thisRule = collect($thisRule)
        ->except("guardians.$id.password")
        ->toArray();
      $thisRule["guardians.$id.relationship"] = [
        'required',
        new Enum(GuardianRelationship::class)
      ];
      if (
        !Student::query()
          ->where('id', $id)
          ->exists()
      ) {
        return throw ValidationException::withMessages([
          'message' => 'Invalid student Id'
        ]);
      }
      $rule = array_merge($thisRule, $rule);
    }
    $data = $request->validate([
      'guardians' => ['required', 'array'],
      ...$rule
    ]);
    // info($data);
    // dd('dkdkd');
    foreach ($data['guardians'] as $studentId => $guardian) {
      RecordGuardian::make($guardian)->create($studentId);
    }

    return $this->ok();
  }

  public function assignStudent(
    Request $request,
    Institution $institution,
    User $guardianUser
  ) {
    $request->validate([
      'relationship' => ['required', new Enum(GuardianRelationship::class)],
      'student_id' => ['required', new ValidateExistsRule(Student::class)]
    ]);

    RecordGuardian::attachStudent(
      $guardianUser,
      $request->student_id,
      $request->relationship
    );
    return $this->ok(['message' => 'Student assigned successfully']);
  }
}
