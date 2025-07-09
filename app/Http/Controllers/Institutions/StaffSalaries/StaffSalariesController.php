<?php

namespace App\Http\Controllers\Institutions\StaffSalaries;

use App\Models\Institution;
use App\DTO\PaymentReferenceDto;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Enums\Payments\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Enums\Payments\PaymentPurpose;
use App\Http\Requests\StaffSalaryRequest;
use App\Models\Funding;
use App\Models\StaffSalary;
use App\Support\Payments\Merchants\PaymentMerchant;
use Inertia\Inertia;

class StaffSalariesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Institution $institution)
  {
    $query = $institution->staffSalaries()->with([
      'salaryType' => function ($query) {
        $query->with('parent');
      },
      'institutionUser.user'
    ]);

    return inertia('institutions/staff-salaries/list-staff-salaries', [
      'staffSalaries' => paginateFromRequest($query),
      'salaryTypes' => $institution->salaryTypes,
      'parentSalaryTypes' => $institution->parentSalaryTypes,
    ]);
  }

  /* == NO LONGER IN USE -- REPLACED WITH A MODAL.
  public function create(Institution $institution)
  {
    return Inertia::render('institutions/staff-salaries/create-edit-staff-salary', [
      'salaryTypes' => $institution->salaryTypes,
      'parentSalaryTypes' => $institution->parentSalaryTypes,
    ]);
  }

  public function edit(Institution $institution, StaffSalary $staffSalary)
  {
    return Inertia::render('institutions/staff-salaries/create-edit-staff-salary', [
      'salaryTypes' => $institution->salaryTypes,
      'parentSalaryTypes' => $institution->parentSalaryTypes,
      'staffSalary' => $staffSalary->load('institutionUser.user')
    ]);
  }
  */

  public function store(Institution $institution, StaffSalaryRequest $request)
  {
    $validatedData = $request->validated();

    //= Check and Prevent duplicate record
    $hasRecord = $institution->staffSalaries()
      ->where('salary_type_id', $validatedData['salary_type_id'])
      ->where('institution_user_id', $validatedData['institution_user_id'])
      ->exists();

    abort_if($hasRecord, 403, 'A similar record already exist for this staff.');

    $institution->staffSalaries()->create($validatedData);
    return $this->ok();
  }

  public function update(Institution $institution, StaffSalaryRequest $request, StaffSalary $staffSalary)
  {
    $validatedData = $request->validated();
    $staffSalary->fill($validatedData)->save();
    return $this->ok();
  }

  public function destroy(Institution $institution, StaffSalary $staffSalary)
  {
    $staffSalary->delete();
    return $this->ok();
  }
}
