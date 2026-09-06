<?php

namespace App\Models;

use App\Enums\InstitutionPermission;
use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Models\CourseTeacher;
use App\Models\GuardianStudent;
use App\Models\Student;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class InstitutionUser extends BaseModel
{
  use HasFactory, SoftDeletes, InstitutionScope, HasRoles;

  protected $guarded = [];
  public $table = 'institution_users';

  protected $appends = ['role'];

  protected $guard_name = 'web';

  protected $casts = [
    'type' => InstitutionUserType::class,
    'institution_id' => 'integer',
    'user_id' => 'integer',
    'status' => InstitutionUserStatus::class
  ];

  /** Legacy clients still consume role; type is the persisted value. */
  public function getRoleAttribute(): ?InstitutionUserType
  {
    return $this->type;
  }

  function hasInstitutionType(InstitutionUserType $type): bool
  {
    return $this->type === $type;
  }

  function hasInstitutionPermission(
    InstitutionPermission|string $permission
  ): bool {
    $permissionName =
      $permission instanceof InstitutionPermission
        ? $permission->value
        : $permission;

    $assignedPermissions = $this->getAllPermissions();
    return $assignedPermissions->contains(
      fn($assignedPermission) => $assignedPermission->name === '*' ||
        $assignedPermission->name === $permissionName
    );
  }

  /**
   * Results are scoped by the user's relationship with the student instead
   * of requiring a separate permission for every result action.
   */
  function canViewResultsFor(Student $student): bool
  {
    $student->loadMissing('classification', 'institutionUser');
    $studentInstitutionId =
      $student->institutionUser?->institution_id ??
      $student->classification?->institution_id;

    if ((int) $studentInstitutionId !== (int) $this->institution_id) {
      return false;
    }

    if ($this->isAdmin() || $student->user_id === $this->user_id) {
      return true;
    }

    if (
      $this->isGuardian() &&
      GuardianStudent::isGuardianOfStudent($this->user_id, $student->id)
    ) {
      return true;
    }

    if (!$this->isTeacher()) {
      return false;
    }

    return $student->classification?->form_teacher_id === $this->user_id ||
      CourseTeacher::query()
        ->where('classification_id', $student->classification_id)
        ->where('user_id', $this->user_id)
        ->exists();
  }

  function canViewAllResults(): bool
  {
    return $this->isAdmin();
  }

  function isSuspended(): bool
  {
    return $this->status === InstitutionUserStatus::Suspended;
  }
  function isActive(): bool
  {
    return $this->status === InstitutionUserStatus::Active;
  }

  function isAdmin()
  {
    return $this->hasInstitutionType(InstitutionUserType::Admin);
  }

  function isTeacher()
  {
    return $this->hasInstitutionType(InstitutionUserType::Teacher);
  }

  function isStudent()
  {
    return $this->hasInstitutionType(InstitutionUserType::Student);
  }

  function isAlumni()
  {
    return $this->hasInstitutionType(InstitutionUserType::Alumni);
  }

  function isGuardian()
  {
    return $this->hasInstitutionType(InstitutionUserType::Guardian);
  }

  function isStaff()
  {
    return in_array(
      $this->type,
      [
        InstitutionUserType::Admin,
        InstitutionUserType::Teacher,
        InstitutionUserType::Accountant
      ],
      true
    );
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function student()
  {
    return $this->hasOne(Student::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }

  function timetableCoordinators()
  {
    return $this->hasMany(TimetableCoordinator::class);
  }

  function salaries()
  {
    return $this->hasMany(Salary::class);
  }

  function payrollAdjustments()
  {
    return $this->hasMany(PayrollAdjustment::class);
  }
  function bankAccounts()
  {
    return $this->morphMany(BankAccount::class, 'accountable');
  }
}
