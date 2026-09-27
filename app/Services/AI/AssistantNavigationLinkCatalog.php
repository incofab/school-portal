<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\Enums\InstitutionPermission;
use App\Enums\InstitutionUserType;

/**
 * Provides trusted, permission-aware destinations for common assistant requests.
 * The assistant may describe these links, but it never invents application URLs.
 */
class AssistantNavigationLinkCatalog
{
  public function for(AssistantActorContext $context): array
  {
    if (!$this->canAccessInstitution($context)) {
      return [];
    }

    $institution = $context->institution;
    $user = $context->institutionUser;
    $links = [
      $this->link(
        'Dashboard',
        'School overview and current term context.',
        'dashboard',
        $institution
      ),
      $this->link(
        'Students',
        'Browse authorized student records.',
        'students.index',
        $institution
      ),
      $this->link(
        'Notifications',
        'View notifications for this institution.',
        'notifications.index',
        $institution
      ),
      $this->link(
        'Chats',
        'Open school conversations.',
        'chats.index',
        $institution,
        [],
        $this->has($user, InstitutionPermission::ManageChat)
      )
    ];

    if (
      $this->isOneOf(
        $user,
        InstitutionUserType::Admin,
        InstitutionUserType::Teacher
      )
    ) {
      $links = [
        ...$links,
        $this->link(
          'Classes',
          'View classes and class groups.',
          'classifications.index',
          $institution
        ),
        $this->link(
          'Subjects',
          'View subjects and courses.',
          'courses.index',
          $institution
        ),
        $this->link(
          'Teacher assignments',
          'View teacher, class, and subject assignments.',
          'course-teachers.index',
          $institution
        ),
        $this->link(
          'Attendance',
          'View attendance records and reports.',
          'attendances.index',
          $institution
        ),
        $this->link(
          'Attendance reports',
          'Review attendance summaries.',
          'attendance-reports.index',
          $institution
        ),
        $this->link(
          'Timetable',
          'View school timetables.',
          'timetables.index',
          $institution
        ),
        $this->link(
          'Result records',
          'View recorded class results.',
          'course-result-info.index',
          $institution
        ),
        $this->link(
          'Result reports',
          'Open academic result reports.',
          'reports.grade-report',
          $institution
        ),
        $this->link(
          'Assignments',
          'View assignments and submissions.',
          'assignments.index',
          $institution
        ),
        $this->link(
          'E-library',
          'Browse library materials.',
          'libraries.index',
          $institution
        )
      ];
    }

    if ($this->has($user, InstitutionPermission::ManageAttendance)) {
      $links = [
        ...$links,
        $this->link(
          'Mark attendance',
          'Record attendance for an authorized class.',
          'attendances.create',
          $institution
        ),
        $this->link(
          'Class register',
          'Open the class attendance register.',
          'attendances.class-register.view',
          $institution
        )
      ];
    }

    if ($this->has($user, InstitutionPermission::ManageFees)) {
      $links = [
        ...$links,
        $this->link(
          'Fees',
          'View configured school fees.',
          'fees.index',
          $institution
        ),
        $this->link(
          'Fee payments',
          'View fee payment records.',
          'fee-payments.index',
          $institution
        ),
        $this->link(
          'Collection overview',
          'Review fee collection totals.',
          'fee-payments.overview',
          $institution
        ),
        $this->link(
          'Payment attempts',
          'Review online payment attempts.',
          'payment-attempts.index',
          $institution
        ),
        $this->link(
          'Manual payments',
          'Review manually recorded payments.',
          'manual-payments.index',
          $institution
        ),
        $this->link(
          'Receipts',
          'Browse payment receipts.',
          'receipts.index',
          $institution
        )
      ];
    }

    if (
      $this->isOneOf(
        $user,
        InstitutionUserType::Student,
        InstitutionUserType::Alumni,
        InstitutionUserType::Guardian
      )
    ) {
      $student = $user->student;
      if ($student) {
        $links = [
          ...$links,
          $this->link(
            'My results',
            'View published results for the current student.',
            'students.term-results.index',
            $institution,
            [$student->id]
          ),
          $this->link(
            'My receipts',
            'View receipts for the current student.',
            'students.receipts.index',
            $institution,
            [$student->id]
          ),
          $this->link(
            'My payment history',
            'View manual payment history.',
            'students.manual-payments.history',
            $institution,
            [$student->id]
          )
        ];
      }

      if ($user->isGuardian()) {
        $links[] = $this->link(
          'My children',
          'View the students linked to this guardian account.',
          'guardians.list-dependents',
          $institution
        );
      }
    }

    if ($user->isAdmin()) {
      $links = [
        ...$links,
        $this->link(
          'School profile',
          'View institution profile details.',
          'profile',
          $institution
        ),
        $this->link(
          'Activity logs',
          'Review institution activity logs.',
          'activity-logs.index',
          $institution
        ),
        $this->link(
          'Messages',
          'Open SMS and email messaging.',
          'messages.index',
          $institution
        ),
        $this->link(
          'Sent notifications',
          'Review sent school notifications.',
          'notifications.sent.index',
          $institution
        )
      ];
    }

    return collect($links)
      ->filter()
      ->unique('url')
      ->values()
      ->all();
  }

  private function canAccessInstitution(AssistantActorContext $context): bool
  {
    return !$context->isGuest &&
      $context->institution !== null &&
      $context->institutionUser?->isActive() === true;
  }

  private function has($user, InstitutionPermission $permission): bool
  {
    return $user?->hasInstitutionPermission($permission) === true;
  }

  private function isOneOf($user, InstitutionUserType ...$types): bool
  {
    return $user && in_array($user->type, $types, true);
  }

  private function link(
    string $label,
    string $description,
    string $routeName,
    $institution,
    array $parameters = [],
    bool $allowed = true
  ): ?array {
    if (!$allowed) {
      return null;
    }

    return [
      'label' => $label,
      'description' => $description,
      'url' => route("institutions.{$routeName}", [
        $institution,
        ...$parameters
      ])
    ];
  }
}
