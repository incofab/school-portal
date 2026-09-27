<?php

namespace App\Actions\Fees;

use App\Models\Fee;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GetFeeCollectionOverview
{
  public function __construct(
    private Institution $institution,
    private Fee $fee
  ) {
  }

  /**
   * Build the fee's eligible student population before applying the optional
   * class and payment-status filters. This keeps the overview aligned with
   * the fee assignment rules already used for fee reminders and payments.
   */
  public function run(
    ?int $classificationId = null,
    ?string $status = null
  ): array {
    $rows = $this->studentRows();

    $scopedRows = $rows->when(
      $classificationId,
      fn(Collection $students) => $students->where(
        'student.classification_id',
        $classificationId
      )
    );

    $summary = $this->summarize($scopedRows);

    $filteredRows = $scopedRows->when(
      $status,
      fn(Collection $students) => $students->where('status', $status)
    );

    return [
      'summary' => $summary,
      'students' => $this->paginate($filteredRows)
    ];
  }

  private function studentRows(): Collection
  {
    $members = (new FeeMembersHandler($this->institution))->getFeeMembers(
      $this->fee
    );

    return $members
      ->filter(fn($member) => $member->student instanceof Student)
      ->unique(fn($member) => $member->student->id)
      ->values()
      ->load('student.classification')
      ->map(function ($member) {
        $student = $member->student;
        $receipt = $member->receipts->first();
        $amountPaid = (float) ($receipt?->amount_paid ?? 0);
        $amountRemaining = max(
          0,
          (float) ($receipt?->amount_remaining ?? $this->fee->amount)
        );

        $student->setRelation('user', $member);

        return [
          'student' => $student,
          'amount_expected' => (float) $this->fee->amount,
          'amount_paid' => $amountPaid,
          'amount_remaining' => $amountRemaining,
          'status' => $this->statusFor($amountPaid, $amountRemaining)
        ];
      });
  }

  private function statusFor(float $amountPaid, float $amountRemaining): string
  {
    if ($amountRemaining < 1) {
      return 'paid';
    }

    return $amountPaid > 0 ? 'partial' : 'unpaid';
  }

  private function summarize(Collection $rows): array
  {
    return [
      'total_students' => $rows->count(),
      'paid' => $rows->where('status', 'paid')->count(),
      'partial' => $rows->where('status', 'partial')->count(),
      'unpaid' => $rows->where('status', 'unpaid')->count(),
      'amount_expected' => $rows->sum('amount_expected'),
      'amount_paid' => $rows->sum('amount_paid'),
      'amount_remaining' => $rows->sum('amount_remaining')
    ];
  }

  private function paginate(Collection $rows): LengthAwarePaginator
  {
    $perPage = max(1, (int) request()->query('perPage', 100));
    $page = max(1, (int) request()->query('page', 1));

    return new LengthAwarePaginator(
      $rows->forPage($page, $perPage)->values(),
      $rows->count(),
      $perPage,
      $page,
      [
        'path' => request()->url(),
        'query' => request()->query()
      ]
    );
  }
}
