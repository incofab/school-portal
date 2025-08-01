<?php
namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Partner;
use App\Models\User;
use App\Models\UserTransaction;
use Illuminate\Database\Eloquent\Model;

class UserTransactionHandler
{
  function __construct(private string $reference)
  {
  }

  static function make(string $reference)
  {
    return new self($reference);
  }

  static function recordTransaction(
    $amount,
    Partner|User $entity,
    TransactionType $transactionType,
    Model $transactionable,
    string $reference,
    bool $isVerified = true,
    $remark = ''
  ) {
    abort_if(
      !$isVerified && UserTransaction::where('reference', $reference)->exists(),
      403,
      'Transaction already evaluated'
    );
    $bbt = $entity->wallet;
    $bat =
      $transactionType === TransactionType::Credit
        ? $bbt + $amount
        : $bbt - $amount;

    if ($bat < 0) {
      throw new \Exception('User wallet cannot be zero or less');
    }

    $entity->fill(['wallet' => $bat])->save();

    //= Save to UserTransactions DB Table
    UserTransaction::Create([
      'type' => $transactionType,
      'amount' => $amount,
      'bbt' => $bbt,
      'bat' => $bat,
      'entity_type' => $entity->getMorphClass(),
      'entity_id' => $entity->id,
      'transactionable_type' => $transactionable?->getMorphClass(),
      'transactionable_id' => $transactionable?->id,
      'reference' => $reference,
      'remark' => $remark
    ]);
  }
}
