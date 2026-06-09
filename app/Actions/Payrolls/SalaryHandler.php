<?php

namespace App\Actions\Payrolls;

use App\Models\Institution;
use App\Models\Salary;
use App\Models\SalaryType;
use App\Support\Audit\FinancialActivityLogger;

class SalaryHandler
{
  public function __construct(private Institution $institution)
  {
  }

  /**
   * @param array {
   *  salary_type_id: int,
   *  description: string,
   *  amount?: float,
   *  institution_user_id: int,
   * } $data
   */
  public function create($data)
  {
    $salaryType = SalaryType::query()
      ->with('parent')
      ->findOrFail($data['salary_type_id']);

    $suppliedAmount = $data['amount'] ?? 0;
    $amount = $this->getAmount(
      $salaryType,
      $data['institution_user_id'],
      $suppliedAmount
    );

    abort_unless($amount > 0, 403, 'Please enter a valid amount');
    $salary = $this->institution->salaries()->firstOrCreate(
      [
        'institution_user_id' => $data['institution_user_id'],
        'salary_type_id' => $data['salary_type_id']
      ],
      [...$data, 'amount' => $amount]
    );

    if ($salary->wasRecentlyCreated) {
      app(FinancialActivityLogger::class)->payrollItemChanged(
        $salary,
        'created'
      );
    }
  }

  /**
   * @param array {
   *  description?: string,
   *  amount?: float,
   * } $data
   */
  public function update(Salary $salary, $data, $canAbort = true)
  {
    $oldValues = $salary->only(['description', 'amount']);
    $salaryType = $salary->salaryType;

    $suppliedAmount = $data['amount'] ?? 0;
    $amount = $this->getAmount(
      $salaryType,
      $salary->institution_user_id,
      $suppliedAmount,
      $canAbort
    );
    abort_unless($amount > 0, 403, 'Please enter a valid amount');
    $salary
      ->fill([
        'description' => $data['description'] ?? $salary->description,
        'amount' => $amount
      ])
      ->save();

    app(FinancialActivityLogger::class)->payrollItemChanged(
      $salary->refresh(),
      'updated',
      $oldValues
    );
  }

  private function getAmount(
    SalaryType $salaryType,
    int $institutionUserId,
    $suppliedAmount = 0,
    $canAbort = true
  ) {
    $parentSalaryType = $salaryType->parent;
    if (!$parentSalaryType) {
      return $suppliedAmount;
    }

    $parentSalary = $parentSalaryType
      ->salaries()
      ->where('institution_user_id', $institutionUserId)
      ->first();
    if (!$parentSalary) {
      abort_if(
        $canAbort,
        401,
        "You have record {$parentSalaryType->title} for this user first"
      );

      return $suppliedAmount;
    }

    return ($salaryType->percentage / 100) * $parentSalary->amount;
  }
}
