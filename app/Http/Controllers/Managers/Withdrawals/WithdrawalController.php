<?php

namespace App\Http\Controllers\Managers\Withdrawals;

use App\Actions\Payments\WithdrawalHandler;
use App\Http\Controllers\Controller;
use App\Enums\WithdrawalStatus;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class WithdrawalController extends Controller
{
  //
  public function index()
  {
    $user = currentUser();

    if ($user->isAdmin()) {
      $bankAccounts = [];
      $withdrawals = Withdrawal::query()->with('bankAccount');
    } elseif ($user->isPartner()) {
      $bankAccounts = $user->partner->bankAccounts()->get();
      $withdrawals = $user->partner->withdrawals()->with('bankAccount');
    }

    return Inertia::render('managers/withdrawals/list-withdrawals', [
      'bankAccounts' => $bankAccounts,
      'withdrawals' => paginateFromRequest($withdrawals)
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'bank_account_id' => 'required|exists:bank_accounts,id',
      'amount' => 'required|numeric',
      'reference' => 'required|string'
    ]);

    $partner = currentUser()->partner;
    $bankAccount = $partner
      ->bankAccounts()
      ->where('id', $validated['bank_account_id'])
      ->firstOrFail();

    $res = WithdrawalHandler::make()->recordPartnerWithdrawal(
      $partner,
      $bankAccount,
      $validated['amount'],
      $validated['reference']
    );

    return $this->apiRes($res, 401);
  }

  public function update(Withdrawal $withdrawal, Request $request)
  {
    $request->validate([
      'status' => [
        'required',
        Rule::in([
          WithdrawalStatus::Declined->value,
          WithdrawalStatus::Paid->value
        ])
      ],
      'remark' => ['nullable', 'string']
    ]);

    $status = $request->status;
    $remark = $request->remark;
    $user = currentUser();
    if ($status === WithdrawalStatus::Declined->value) {
      WithdrawalHandler::make()->declineWithdrawal($withdrawal, $user, $remark);
    } elseif ($status === WithdrawalStatus::Paid->value) {
      WithdrawalHandler::make()->confirmWithdrawal($withdrawal, $user, $remark);
    }
    return $this->ok();
  }
}
