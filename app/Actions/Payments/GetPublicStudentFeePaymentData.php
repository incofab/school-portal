<?php

namespace App\Actions\Payments;

use App\Enums\PaymentInterval;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Student;
use App\Support\SettingsHandler;

class GetPublicStudentFeePaymentData
{
  public function __construct(
    private Institution $institution,
    private Student $student
  ) {
  }

  public static function make(Institution $institution, Student $student)
  {
    return new self($institution, $student);
  }

  public function run(): array
  {
    $settings = SettingsHandler::makeFromInstitution($this->institution);
    $term = $settings->getCurrentTerm();
    $academicSessionId = $settings->getCurrentAcademicSession();
    $academicSession = $academicSessionId
      ? AcademicSession::find($academicSessionId)
      : null;

    $this->student->loadMissing('institutionUser', 'classification', 'user');

    return [
      'institution' => $this->institution->loadMissing('institutionGroup'),
      'student' => $this->student,
      'fees' => collect(
        $this->student->studentFees($term, $academicSessionId)
      )
        ->map(fn(Fee $fee) => $this->formatFee($fee))
        ->values(),
      'academicSession' => $academicSession,
      'term' => $term,
      'bankAccounts' => $this->institution->institutionGroup
        ->bankAccounts()
        ->get()
    ];
  }

  private function formatFee(Fee $fee): array
  {
    $receipt = FeePaymentHandler::getReceipt($fee, $this->student->user);
    $amountPaid = (float) ($receipt?->amount_paid ?? 0);
    $amountRemaining = max(
      0,
      (float) ($receipt?->amount_remaining ?? $fee->amount)
    );

    return [
      'id' => $fee->id,
      'title' => $fee->title,
      'amount' => (float) $fee->amount,
      'amount_paid' => $amountPaid,
      'amount_remaining' => $amountRemaining,
      'status' => $amountRemaining < 1
        ? 'paid'
        : ($amountPaid > 0 ? 'partial' : 'due'),
      'term' => $fee->term?->value ?? $fee->term,
      'payment_interval' => $fee->payment_interval instanceof PaymentInterval
        ? $fee->payment_interval->value
        : $fee->payment_interval,
      'academic_session' => $fee->academicSession?->title,
      'fee_items' => $fee->fee_items
    ];
  }
}
