<?php

namespace App\Http\Controllers\Institutions\SalaryTypes;

use App\Models\Institution;
use App\DTO\PaymentReferenceDto;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Enums\Payments\PaymentMerchantType;
use App\Http\Controllers\Controller;
use App\Enums\Payments\PaymentPurpose;
use App\Http\Requests\SalaryTypeRequest;
use App\Models\Funding;
use App\Models\SalaryType;
use App\Models\StaffSalary;
use App\Support\Payments\Merchants\PaymentMerchant;
use Inertia\Inertia;

class SalaryTypesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function search()
  {
    return response()->json([
      'result' => SalaryType::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->latest('title')
        ->get()
    ]);
  }

  public function index(Institution $institution)
  {
    $query = $institution->salaryTypes()->with(['parent'])->latest('id');

    return inertia('institutions/salary-types/list-salary-types', [
      'salaryTypes' => paginateFromRequest($query),
      'salaryTypesArray' => $query->get()
    ]);
  }

  public function store(Institution $institution, SalaryTypeRequest $request)
  {
    $validatedData = $request->validated();

    //= Check and Prevent duplicate record
    $hasRecord = $institution->salaryTypes()
      ->where('title', $validatedData['title'])
      ->where('type', $validatedData['type'])
      ->exists();

    abort_if($hasRecord, 403, 'A similar record already exist.');

    $institution->salaryTypes()->create($validatedData);
    return $this->ok();
  }

  public function update(Institution $institution, SalaryTypeRequest $request, SalaryType $salaryType)
  {
    $validatedData = $request->validated();
    $salaryType->fill($validatedData)->save();
    return $this->ok();
  }

  public function destroy(Institution $institution, SalaryType $salaryType)
  {
    if (count($salaryType->staffSalaries) > 0 || count($salaryType->children) > 0) {
      return $this->message(
        "This record can not be deleted because it is associated with some Staff Salaries or other Salary Types.",
        403
      );
    }

    $salaryType->delete();
    return $this->ok();
  }
}
