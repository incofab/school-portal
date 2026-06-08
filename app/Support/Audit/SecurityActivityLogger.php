<?php

namespace App\Support\Audit;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SecurityActivityLogger
{
  public function loginSucceeded(
    User $user,
    ?Institution $institution,
    string $guard = 'web'
  ): void {
    app(ActivityLogger::class)
      ->event('auth.login_succeeded')
      ->category(ActivityLogCategory::Authentication)
      ->action('login_succeeded')
      ->by($user, $guard)
      ->on($user)
      ->inInstitution($institution)
      ->description('User login succeeded.')
      ->properties(['guard' => $guard])
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function loginFailed(
    string $identifier,
    string $identifierType = 'email',
    ?Institution $institution = null,
    string $guard = 'web',
    ?Model $subject = null
  ): void {
    app(ActivityLogger::class)
      ->event('auth.login_failed')
      ->category(ActivityLogCategory::Authentication)
      ->action('login_failed')
      ->on($subject)
      ->inInstitution($institution)
      ->description('User login failed.')
      ->properties([
        'guard' => $guard,
        'identifier_type' => $identifierType,
        'identifier' => $identifier
      ])
      ->severity(ActivityLogSeverity::Warning)
      ->log();
  }

  public function logout(
    User $user,
    ?Institution $institution,
    bool $wasImpersonating
  ): void {
    app(ActivityLogger::class)
      ->event('auth.logout')
      ->category(ActivityLogCategory::Authentication)
      ->action('logged_out')
      ->by($user)
      ->on($user)
      ->inInstitution($institution)
      ->description('User logged out.')
      ->properties(['was_impersonating' => $wasImpersonating])
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function passwordResetRequested(string $email): void
  {
    app(ActivityLogger::class)
      ->event('auth.password_reset_requested')
      ->category(ActivityLogCategory::Authentication)
      ->action('password_reset_requested')
      ->description('Password reset was requested.')
      ->properties(['email' => $email])
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function passwordResetCompleted(User $user): void
  {
    app(ActivityLogger::class)
      ->event('auth.password_reset_completed')
      ->category(ActivityLogCategory::Authentication)
      ->action('password_reset_completed')
      ->by($user)
      ->on($user)
      ->description('Password reset was completed.')
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function passwordChanged(
    User $user,
    ?Institution $institution = null
  ): void {
    app(ActivityLogger::class)
      ->event('auth.password_changed')
      ->category(ActivityLogCategory::Authentication)
      ->action('password_changed')
      ->by($user)
      ->on($user)
      ->inInstitution($institution)
      ->description('User changed their password.')
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function passwordResetByAdmin(
    User $actor,
    User $target,
    ?Institution $institution = null
  ): void {
    app(ActivityLogger::class)
      ->event('auth.password_changed')
      ->category(ActivityLogCategory::Authentication)
      ->action('password_reset_by_admin')
      ->by($actor)
      ->on($target)
      ->inInstitution($institution)
      ->description('User password was reset by an administrator.')
      ->severity(ActivityLogSeverity::Critical)
      ->log();
  }

  public function identityUpdated(
    User $user,
    string $identityType,
    bool $wasPreviouslySet
  ): void {
    app(ActivityLogger::class)
      ->event('access.identity_updated')
      ->category(ActivityLogCategory::Security)
      ->action('identity_updated')
      ->by($user)
      ->on($user)
      ->description('User security identity was updated.')
      ->properties([
        'identity_type' => $identityType,
        'was_previously_set' => $wasPreviouslySet
      ])
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function userCreated(
    User $actor,
    User $target,
    Institution $institution,
    string $role
  ): void {
    app(ActivityLogger::class)
      ->event('access.user_created')
      ->category(ActivityLogCategory::User)
      ->action('user_created')
      ->by($actor)
      ->on($target)
      ->inInstitution($institution)
      ->description('User was created.')
      ->newValues(['role' => $role])
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function userStatusChanged(
    User $actor,
    InstitutionUser $target,
    Institution $institution,
    ?string $oldStatus,
    string $newStatus,
    ?string $statusMessage = null
  ): void {
    app(ActivityLogger::class)
      ->event('access.user_status_changed')
      ->category(ActivityLogCategory::User)
      ->action('user_status_changed')
      ->by($actor)
      ->on($target->user)
      ->inInstitution($institution)
      ->description('User status was changed.')
      ->oldValues(['status' => $oldStatus])
      ->newValues(['status' => $newStatus, 'status_message' => $statusMessage])
      ->severity(ActivityLogSeverity::Security)
      ->log();
  }

  public function userDeleted(
    User $actor,
    User $target,
    Institution $institution,
    ?string $role = null
  ): void {
    app(ActivityLogger::class)
      ->event('access.user_deleted')
      ->category(ActivityLogCategory::User)
      ->action('user_deleted')
      ->by($actor)
      ->on($target)
      ->inInstitution($institution)
      ->description('User was deleted.')
      ->oldValues(['role' => $role])
      ->severity(ActivityLogSeverity::Critical)
      ->log();
  }

  public function roleChanged(
    User $actor,
    InstitutionUser $target,
    Institution $institution,
    string $oldRole,
    string $newRole
  ): void {
    app(ActivityLogger::class)
      ->event('access.role_changed')
      ->category(ActivityLogCategory::Authorization)
      ->action('role_changed')
      ->by($actor)
      ->on($target->user)
      ->inInstitution($institution)
      ->description('User role was changed.')
      ->oldValues(['role' => $oldRole])
      ->newValues(['role' => $newRole])
      ->severity(ActivityLogSeverity::Critical)
      ->log();
  }

  public function permissionChanged(
    User $actor,
    Model $target,
    array $oldPermissions,
    array $newPermissions
  ): void {
    app(ActivityLogger::class)
      ->event('access.permission_changed')
      ->category(ActivityLogCategory::Authorization)
      ->action('permission_changed')
      ->by($actor)
      ->on($target)
      ->description('Permissions were changed.')
      ->oldValues(['permissions' => $oldPermissions])
      ->newValues(['permissions' => $newPermissions])
      ->severity(ActivityLogSeverity::Critical)
      ->log();
  }

  public function impersonationStarted(
    User $impersonator,
    User $target,
    ?Institution $institution,
    string $type
  ): void {
    app(ActivityLogger::class)
      ->event('access.impersonation_started')
      ->category(ActivityLogCategory::Impersonation)
      ->action('impersonation_started')
      ->by($impersonator)
      ->on($target)
      ->inInstitution($institution)
      ->description('User impersonation started.')
      ->properties(['impersonation_type' => $type])
      ->severity(ActivityLogSeverity::Critical)
      ->log();
  }

  public function impersonationStopped(
    User $impersonator,
    User $target,
    ?Institution $institution,
    ?string $type
  ): void {
    app(ActivityLogger::class)
      ->event('access.impersonation_stopped')
      ->category(ActivityLogCategory::Impersonation)
      ->action('impersonation_stopped')
      ->by($impersonator)
      ->on($target)
      ->inInstitution($institution)
      ->description('User impersonation stopped.')
      ->properties(['impersonation_type' => $type])
      ->severity(ActivityLogSeverity::Critical)
      ->log();
  }

  public function unauthorizedAccess(
    ?User $actor,
    string $message,
    ?Institution $institution = null,
    ?Model $subject = null
  ): void {
    app(ActivityLogger::class)
      ->event('access.unauthorized')
      ->category(ActivityLogCategory::Authorization)
      ->action('unauthorized_access')
      ->by($actor)
      ->on($subject)
      ->inInstitution($institution)
      ->description($message)
      ->severity(ActivityLogSeverity::Warning)
      ->log();
  }

  public function studentLoginSucceeded(
    Student $student,
    Institution $institution
  ): void {
    $student->loadMissing('user');

    $this->loginSucceeded($student->user, $institution, 'student');
  }

  public function studentLoginFailed(
    string $studentCode,
    ?Student $student = null
  ): void {
    $student?->loadMissing('user', 'institutionUser.institution');

    $this->loginFailed(
      $studentCode,
      'student_code',
      $student?->institutionUser?->institution,
      'student',
      $student?->user
    );
  }
}
