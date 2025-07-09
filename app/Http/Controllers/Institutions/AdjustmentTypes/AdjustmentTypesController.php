<?php

namespace App\Http\Controllers\Institutions\AdjustmentTypes;

use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustmentTypeRequest;
use App\Models\AdjustmentType;
use Inertia\Inertia;

class AdjustmentTypesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function search()
  {
    return response()->json([
      'result' => AdjustmentType::query()
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
    $query = $institution->adjustmentTypes()->with(['parent'])->latest('id');

    return inertia('institutions/adjustment-types/list-adjustment-types', [
      'adjustmentTypes' => paginateFromRequest($query),
      'parentAdjustmentTypes' => $institution->parentAdjustmentTypes, //AdjustmentTypes that are not children of another type.
    ]);
  }

  public function store(Institution $institution, AdjustmentTypeRequest $request)
  {
    $validatedData = $request->validated();

    //= Check and Prevent duplicate record
    $hasRecord = $institution->adjustmentTypes()
      ->where('title', $validatedData['title'])
      ->where('type', $validatedData['type'])
      ->exists();

    abort_if($hasRecord, 403, 'A similar record already exist.');


    $institution->adjustmentTypes()->create($validatedData);
    return $this->ok();
  }

  public function update(Institution $institution, AdjustmentTypeRequest $request, AdjustmentType $adjustmentType)
  {
    $validatedData = $request->validated();
    $adjustmentType->fill($validatedData)->save();
    return $this->ok();
  }

  public function destroy(Institution $institution, AdjustmentType $adjustmentType)
  {
    if (count($adjustmentType->salaryAdjustments) > 0 || count($adjustmentType->children) > 0) {
      return $this->message(
        "This record can not be deleted because it is associated with some Salary Adjustments or other Adjustment Types.",
        403
      );
    }

    $adjustmentType->delete();
    return $this->ok();
  }
}
