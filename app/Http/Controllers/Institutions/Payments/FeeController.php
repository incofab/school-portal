<?php

namespace App\Http\Controllers\Institutions\Payments;

use App\Actions\Payments\RecordFee;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFeeRequest;
use App\Models\AcademicSession;
use App\Models\Association;
use App\Models\Fee;
use App\Models\Institution;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\FinancialActivityLogger;
use App\Support\UITableFilters\FeeUITableFilters;
use Illuminate\Http\Request;

class FeeController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ])->except(['index', 'search']);
  }

  public function index(Request $request, Institution $institution)
  {
    $filter = FeeUITableFilters::make(
      $request->all(),
      Fee::query()
    )->filterQuery();
    $query = $filter->getQuery()->with('feeCategories.feeable');

    return inertia('institutions/payments/list-fees', [
      'fees' => paginateFromRequest($query),
      'term' => $filter->getTerm(),
      'academicSession' => AcademicSession::find($filter->getAcademicSession())
    ]);
  }

  public function search(Institution $institution)
  {
    return response()->json([
      'result' => Fee::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->orderBy('title')
        ->with('feeCategories')
        ->take(100)
        ->get()
    ]);
  }

  public function create(Institution $institution)
  {
    return inertia('institutions/payments/create-edit-fee', [
      'associations' => Association::all(),
      'feeTemplates' => Fee::query()
        ->with(['academicSession', 'feeCategories.feeable'])
        ->latest()
        ->take(30)
        ->get()
    ]);
  }

  public function store(CreateFeeRequest $request, Institution $institution)
  {
    $data = $request->validated();
    $fee = RecordFee::run($data, $institution);

    return $this->ok(['fee' => $fee]);
  }

  public function edit(Institution $institution, Fee $fee)
  {
    $fee->load('feeCategories.feeable');

    return inertia('institutions/payments/create-edit-fee', [
      'associations' => Association::all(),
      'fee' => $fee
    ]);
  }

  public function update(
    Institution $institution,
    Fee $fee,
    CreateFeeRequest $request
  ) {
    RecordFee::run($request->validated(), $institution, $fee);

    return $this->ok();
  }

  public function destroy(Institution $institution, Fee $fee)
  {
    abort_if($fee->feePayments()->first(), 403, 'Fee has payments');
    $fee->loadMissing('feeCategories.feeable');
    $oldValues = $fee->only([
      'title',
      'amount',
      'payment_interval',
      'academic_session_id',
      'term',
      'fee_items'
    ]);

    ModelAudit::withoutAuditingFor(Fee::class, function () use ($fee) {
      $fee->delete();
    });

    app(FinancialActivityLogger::class)->feeRecorded(
      $fee,
      'deleted',
      $oldValues
    );

    return $this->ok();
  }
}
