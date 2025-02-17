<?php

namespace App\Support\Fundings;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FundingHandler
{
    private $principalAmount;
    private $debtReference;
    private $creditReference;

    /**
     * @param array{
     *  amount: int|float,
     *  reference: string,
     *  remark: string
     * } $data
     */
    function __construct(private InstitutionGroup $institutionGroup, private User $user, private array $data)
    {
        $this->principalAmount = $data['amount'];
        $this->debtReference = Funding::debtReference($data['reference']);
        $this->creditReference = Funding::creditReference($data['reference']);
    }

    static function makeFromPaymentRef(PaymentReference $paymentRef): static
    {
        return new self($paymentRef->institution->institutionGroup, $paymentRef->user, [
            'reference' => $paymentRef->reference,
            'amount' => $paymentRef->amount,
        ]);
    }

    function run(WalletType $fundingType, ?Model $fundable = null)
    {
        if ($fundingType === WalletType::Debt) {
            $this->giveLoan($this->principalAmount, $fundable);
            return;
        }

        $surplus = $this->payDebt($this->principalAmount);
        if ($surplus > 0) {
            $this->fundCreditWallet($surplus, $fundable);
        }
    }

    function giveLoan($amount, ?Model $fundable = null)
    {
        $this->fundDebtWallet($amount, TransactionType::Credit);
        $this->fundCreditWallet($amount, $fundable);
    }

    function payDebt($amount)
    {
        if (!$this->institutionGroup->isOwing()) {
            return $amount;
        }
        $prevDebtBal = $this->institutionGroup->debt_wallet;

        $debtToPay = $amount > $prevDebtBal ? $prevDebtBal : $amount;
        $this->fundDebtWallet($debtToPay, TransactionType::Debit);
        return $amount - $prevDebtBal;
    }

    function fundCreditWallet($amount, ?Model $fundable = null)
    {
        $prevCreditBal = $this->institutionGroup->credit_wallet;
        $newCreditBal = $prevCreditBal + $amount;

        $this->institutionGroup->fill(['credit_wallet' => $newCreditBal])->save();

        $funding = $this->institutionGroup->fundings()->firstOrCreate(
            ['reference' => $this->creditReference],
            [
                'amount' => $amount,
                'wallet' => WalletType::Credit->value,
                'previous_balance' => $prevCreditBal,
                'new_balance' => $newCreditBal,

                'funded_by_user_id' => $this->user->id,
                'remark' => $this->data['remark'] ?? '',
                'fundable_id' => $fundable?->id,
                'fundable_type' => $fundable?->getMorphClass(),
            ]
        );

        $funding->transactions()->firstOrCreate(
            ['reference' => $this->creditReference],
            [
                'institution_group_id' => $this->institutionGroup->id,
                'wallet' => WalletType::Credit->value,
                'amount' => $amount,
                'type' => TransactionType::Credit->value,
                'bbt' => $prevCreditBal,
                'bat' => $newCreditBal,
            ]
        );
    }


    function fundDebtWallet($amount, TransactionType $type)
    {
        $prevDebtBal = $this->institutionGroup->debt_wallet;
        $newDebtBal = $type === TransactionType::Credit ? $amount + $prevDebtBal : $amount - $prevDebtBal;

        $funding = $this->institutionGroup->fundings()->firstOrCreate([
            'reference' => $this->debtReference
        ], [
            'amount' => $amount,
            'previous_balance' => $prevDebtBal,
            'new_balance' => $newDebtBal,

            'wallet' => WalletType::Debt->value,
            'remark' => $this->data['remark'] ?? '',
            'funded_by_user_id' => $this->user->id,

        ]);

        $this->institutionGroup->fill(['debt_wallet' => $newDebtBal])->save();

        $funding->transactions()->firstOrCreate(
            [
                'reference' => $this->debtReference,
            ],
            [
                'wallet' => WalletType::Debt->value,
                'amount' => $amount,
                'type' => $type,
                'bbt' => $prevDebtBal,
                'bat' => $newDebtBal,

                'institution_group_id' => $this->institutionGroup->id,
            ]
        );
    }
}
