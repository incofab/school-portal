<?php

namespace App\Policies;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Models\User;

class PaymentReferencePolicy
{
  public function viewOwn(User $user, PaymentReference $paymentReference): bool
  {
    return $this->belongsToUser($user, $paymentReference);
  }

  public function verifyOwn(
    User $user,
    PaymentReference $paymentReference
  ): bool {
    return $this->belongsToUser($user, $paymentReference);
  }

  public function viewInstitution(
    User $user,
    PaymentReference $paymentReference,
    Institution $institution
  ): bool {
    if ($paymentReference->institution_id !== $institution->id) {
      return false;
    }

    return $this->canManageInstitutionPayments($institution) ||
      $this->belongsToUser($user, $paymentReference);
  }

  public function verifyInstitution(
    User $user,
    PaymentReference $paymentReference,
    Institution $institution
  ): bool {
    return $this->viewInstitution($user, $paymentReference, $institution);
  }

  private function belongsToUser(
    User $user,
    PaymentReference $paymentReference
  ): bool {
    return $paymentReference->user_id === $user->id ||
      ($paymentReference->payable_type === $user->getMorphClass() &&
        $paymentReference->payable_id === $user->id);
  }

  private function canManageInstitutionPayments(Institution $institution): bool
  {
    $institutionUser = currentInstitutionUser();

    return $institutionUser?->institution_id === $institution->id &&
      in_array($institutionUser->role, [
        InstitutionUserType::Admin,
        InstitutionUserType::Accountant
      ]);
  }
}
