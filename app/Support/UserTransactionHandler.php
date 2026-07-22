<?php
namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Partner;
use App\Models\User;
use App\Models\UserTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
    return DB::transaction(function () use (
      $amount,
      $entity,
      $transactionType,
      $transactionable,
      $reference,
      $isVerified,
      $remark
    ) {
      $lockedEntity = $entity->freshWithLockForUpdate();

      $existingTransaction = UserTransaction::query()
        ->where('reference', $reference)
        ->first();

      abort_if(
        !$isVerified && $existingTransaction,
        403,
        'Transaction already evaluated'
      );

      if ($existingTransaction) {
        return $existingTransaction;
      }

      $bbt = $lockedEntity->wallet;
      $bat =
        $transactionType === TransactionType::Credit
          ? $bbt + $amount
          : $bbt - $amount;

      if ($bat < 0) {
        throw new \Exception('User wallet cannot be zero or less');
      }

      $lockedEntity->fill(['wallet' => $bat])->save();

      return UserTransaction::query()->create([
        'type' => $transactionType,
        'amount' => $amount,
        'bbt' => $bbt,
        'bat' => $bat,
        'entity_type' => $lockedEntity->getMorphClass(),
        'entity_id' => $lockedEntity->id,
        'transactionable_type' => $transactionable?->getMorphClass(),
        'transactionable_id' => $transactionable?->id,
        'reference' => $reference,
        'remark' => $remark
      ]);
    });
  }
}
