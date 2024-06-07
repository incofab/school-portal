<?php
namespace App\Http\Controllers\Institutions\Payments;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReceiptTypeRequest;
use App\Models\Institution;
use App\Models\ReceiptType;

class ReceiptTypeController extends Controller
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
    $query = ReceiptType::query();
    return inertia('institutions/payments/list-receipt-types', [
      'receiptTypes' => paginateFromRequest($query)
    ]);
  }

  function search()
  {
    return response()->json([
      'result' => ReceiptType::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->orderBy('title')
        ->get()
    ]);
  }

  function store(CreateReceiptTypeRequest $request, Institution $institution)
  {
    $data = $request->validated();
    $receiptTypes = $institution->receiptTypes()->create($data);
    return $this->ok(['fee' => $receiptTypes]);
  }

  function update(
    Institution $institution,
    ReceiptType $receiptType,
    CreateReceiptTypeRequest $request
  ) {
    $receiptType->fill($request->validated())->save();
    return $this->ok();
  }

  function destroy(Institution $institution, ReceiptType $receiptType)
  {
    $receiptType->delete();
    return $this->ok();
  }
}
