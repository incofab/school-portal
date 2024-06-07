<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFeeRequest;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\ReceiptType;

class FeeController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ])->except(['index', 'search']);
  }

  function index()
  {
    $query = Fee::query()->with(
      'receiptType',
      'classification',
      'classificationGroup'
    );
    return inertia('institutions/payments/list-fees', [
      'fees' => paginateFromRequest($query)
    ]);
  }

  function search()
  {
    return response()->json([
      'result' => Fee::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->orderBy('title')
        ->with('receiptType', 'classification', 'classificationGroup')
        ->get()
    ]);
  }

  function create()
  {
    return inertia('institutions/payments/create-edit-fee', [
      'receiptTypes' => ReceiptType::all(),
      'classificationGroups' => ClassificationGroup::all(),
      'classifications' => Classification::all()
    ]);
  }

  function store(CreateFeeRequest $request, Institution $institution)
  {
    $data = $request->validated();
    $fee = $institution->fees()->create($data);
    return $this->ok(['fee' => $fee]);
  }

  function edit(Institution $institution, Fee $fee)
  {
    return inertia('institutions/payments/create-edit-fee', [
      'fee' => $fee,
      'receiptTypes' => ReceiptType::all(),
      'classificationGroups' => ClassificationGroup::all(),
      'classifications' => Classification::all()
    ]);
  }

  function update(Institution $institution, Fee $fee, CreateFeeRequest $request)
  {
    $fee->fill($request->validated())->save();
    return $this->ok();
  }

  function destroy(Institution $institution, Fee $fee)
  {
    $fee->delete();
    return $this->ok();
  }
}
