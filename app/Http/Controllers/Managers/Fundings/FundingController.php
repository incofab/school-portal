<?php

namespace App\Http\Controllers\Managers\Fundings;

use App\Enums\TransactionType;
use Inertia\Inertia;
use App\Models\Funding;
use App\Enums\WalletType;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\InstitutionGroup;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Rules\ValidateFundingReference;
use App\Support\Fundings\FundingHandler;

class FundingController extends Controller
{
    public function index(Request $request)
    {
        $user = currentUser();

        if (!$user->isAdmin()) {
            abort(400, "Ünauthorized");
        }

        $fundings = Funding::with('institutionGroup')->latest('id');

        return Inertia::render(
            'managers/fundings/list-fundings',
            [
                'fundings' => paginateFromRequest($fundings),
                'institutionGroups' => InstitutionGroup::all()
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_group_id' => 'required|exists:institution_groups,id',
            'amount' => 'required|numeric',
            'remark' => 'nullable|string',
            'reference' => ['required', new ValidateFundingReference()],
        ]);

        $institutionGroup = InstitutionGroup::find($validated['institution_group_id']);
        $obj = new FundingHandler($institutionGroup, currentUser(), $validated);

        // $type = WalletType::from($validated['type']);
        $type = $request->is_debt ? WalletType::Debt : WalletType::Credit;
        $obj->run($type);

        return $this->ok();



        // $amount = $validated['amount']; //Principal Amount
        // $fundingType = $validated['type'];

        // //== Settle any DEBT_WALLET Balance before adding the subplus to the CREDIT_WALLET.


        // if ($fundingType === WalletType::Credit->value) {

        //     $prevDebtBal = $getInstGroup->debt_wallet;
        //     $surplus = $amount - $prevDebtBal;

        //     if ($prevDebtBal > 0) {
        //         $this->repay_debt($validated, $getInstGroup, $amount);
        //     }

        //     if ($surplus > 0) {
        //         $this->fund_credit_wallet($validated, $getInstGroup, $surplus);
        //     }
        // } else {
        //     $this->fund_debt_wallet($validated, $getInstGroup, $amount);
        //     $this->fund_credit_wallet($validated, $getInstGroup, $amount);
        // }

        // return $this->ok();
    }

    /*
    function repay_debt($validated, $getInstGroup, $amount)
    {
        $prevDebtBal = $getInstGroup->debt_wallet;

        //== Calculate amount to be repaid
        if ($amount > $prevDebtBal) {
            $amt_to_repay = $prevDebtBal;
        } else {
            $amt_to_repay = $amount;
        }

        //== Update the DEBT_WALLET balance
        $newDebtBal = $prevDebtBal - $amt_to_repay;
        $getInstGroup->update(['debt_wallet' => $newDebtBal]);

        //== Save record to FUNDINGS table
        $funding = Funding::create([
            'institution_group_id' => $validated['institution_group_id'],
            'amount' => $amt_to_repay,
            'remark' => $validated['remark'],
            'reference' => "{$validated['reference']}-debt",
            'wallet' => WalletType::Debt->value,
            'funded_by_user_id' => currentUser()->id,
            'previous_balance' => $prevDebtBal,
            'new_balance' => $newDebtBal,
        ]);

        //== Save record to TRANSACTIONS table
        $funding->transactions()->create([
            'institution_group_id' => $getInstGroup->id,
            'wallet' => WalletType::Debt->value,
            'amount' => $funding->amount,
            'type' => TransactionType::Debit->value,
            'bbt' => $prevDebtBal,
            'bat' => $newDebtBal,
            'reference' => $funding->reference,
        ]);
    }

    function fund_credit_wallet($validated, $getInstGroup, $amount)
    {
        $prevCreditBal = $getInstGroup->credit_wallet;
        $newCreditBal = $prevCreditBal + $amount;

        //== Update the CREDIT_WALLET balance
        $getInstGroup->update(['credit_wallet' => $newCreditBal]);

        //== Save record to FUNDINGS table
        $funding = Funding::create([
            'institution_group_id' => $validated['institution_group_id'],
            'amount' => $amount,
            'remark' => $validated['remark'],
            'reference' => "{$validated['reference']}-credit",
            'wallet' => WalletType::Credit->value,
            'funded_by_user_id' => currentUser()->id,
            'previous_balance' => $prevCreditBal,
            'new_balance' => $newCreditBal,
        ]);

        //== Save record to TRANSACTIONS table
        $funding->transactions()->create([
            'institution_group_id' => $getInstGroup->id,
            'wallet' => WalletType::Credit->value,
            'amount' => $funding->amount,
            'type' => TransactionType::Credit->value,
            'bbt' => $prevCreditBal,
            'bat' => $newCreditBal,
            'reference' => $funding->reference,
        ]);
    }

    function fund_debt_wallet($validated, $getInstGroup, $amount)
    {
        $prevDebtBal = $getInstGroup->debt_wallet;
        $newDebtBal = $prevDebtBal + $amount;

        //== Update the DEBT_WALLET balance
        $getInstGroup->update(['debt_wallet' => $newDebtBal]);

        //== Save record to FUNDINGS table
        $funding = Funding::create([
            'institution_group_id' => $validated['institution_group_id'],
            'amount' => $amount,
            'remark' => $validated['remark'],
            'reference' => "{$validated['reference']}-debt",
            'wallet' => WalletType::Debt->value,
            'funded_by_user_id' => currentUser()->id,
            'previous_balance' => $prevDebtBal,
            'new_balance' => $newDebtBal,
        ]);

        //== Save record to TRANSACTIONS table
        $funding->transactions()->create([
            'institution_group_id' => $getInstGroup->id,
            'wallet' => WalletType::Debt->value,
            'amount' => $funding->amount,
            'type' => TransactionType::Credit->value,
            'bbt' => $prevDebtBal,
            'bat' => $newDebtBal,
            'reference' => $funding->reference,
        ]);
    }

    public function storeXX(Request $request)
    {
        $validated = $request->validate([
            'institution_group_id' => 'required|exists:institution_groups,id',
            'amount' => 'required|numeric',
            'remark' => 'nullable|string',
            'reference' => 'required|string',
        ]);

        $getInstGroup = InstitutionGroup::find($validated['institution_group_id']);

        $amount = $validated['amount'];
        $prevBal = $getInstGroup->wallet_balance;
        $newBal = $prevBal + $amount;

        $getInstGroup->update(['wallet_balance' => $newBal]);

        $funding = Funding::create([
            ...$validated,
            'funded_by_user_id' => currentUser()->id,
            'previous_balance' => $prevBal,
            'new_balance' => $newBal,
        ]);

        $funding->transactions()->create([
            'transaction_id' => $funding->id,
            'institution_group_id' => $getInstGroup->id,
            'amount' => $amount,
            'bbt' => $prevBal,
            'bat' => $newBal,
            'reference' => "funding-{$funding->reference}",
        ]);

        return $this->ok();
    }
    */
}