<?php

namespace App\Http\Controllers;

use App\Core\MonnifyHelper;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
  public function __construct()
  {
  }

  function search()
  {
    return response()->json([
      'result' => Bank::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('bank_name', 'like', "%$search%")
        )
        ->orderBy('bank_name')
        ->get()
    ]);
  }

  function validateBankAccount(Request $request)
  {
    $request->validate([
      'bank_code' => ['required'],
      'account_number' => ['required']
    ]);
    $res = MonnifyHelper::make()->validateBankAccount(
      $request->bank_code,
      $request->account_number
    );
    return $res->isSuccessful()
      ? $this->ok($res->toArray())
      : $this->message($res->message, 403);
  }
}
