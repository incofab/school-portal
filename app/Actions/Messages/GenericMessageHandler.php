<?php
namespace App\Actions\Messages;

use App\Enums\NotificationChannelsType;
use App\Enums\PriceLists\PriceType;
use App\Jobs\SendBulksms;
use App\Mail\InstitutionMessageMail;
use App\Models\Association;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use App\Support\Res;
use App\Support\TransactionHandler;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GenericMessageHandler
{
  function __construct(
    private Institution $institution,
    private User $senderUser,
    private string $message,
    private ?string $subject = ''
  ) {
  }

  function sendToUsers(
    ?Model $messageable,
    $channel,
    $forGuardians = false
  ): Res {
    $users = $this->getUsers($messageable, $forGuardians);

    DB::beginTransaction();
    $messageModel = (new RecordMessage($this->institution, $this->senderUser, [
      'subject' => $this->subject ?? 'Generic Message',
      'body' => $this->message,
      'channel' => $channel
    ]))
      ->forModel($messageable)
      ->save();

    if ($channel === NotificationChannelsType::Sms->value) {
      $receivers = $users->map(fn($user) => $user->phone);
    } else {
      $receivers = $users->map(fn($user) => $user->email);
    }
    $res = $this->submit($receivers, $channel, $messageModel);
    if ($res->isNotSuccessful()) {
      DB::rollback();
      return $res;
    }
    DB::commit();
    return $res;
  }

  function sendToReceivers(Collection $receivers, $channel): Res
  {
    DB::beginTransaction();
    $recordMessage = new RecordMessage($this->institution, $this->senderUser, [
      'subject' => $this->subject ?? 'Generic Message',
      'body' => $this->message,
      'channel' => $channel
    ]);

    $messageModel = null;
    if ($receivers->count() < 2) {
      $messageModel = $recordMessage->forSingle($receivers->first())->save();
    } else {
      $messageModel = $recordMessage
        ->forMultiple($receivers->toArray())
        ->save();
    }

    $res = $this->submit($receivers, $channel, $messageModel);
    if ($res->isNotSuccessful()) {
      DB::rollback();
      return $res;
    }
    DB::commit();
    return $res;
  }

  function getUsers(?Model $model, $forGuardians = false): Collection
  {
    $users = collect();
    if (!$model) {
      return $users;
    }
    if ($model instanceof Classification) {
      $users = User::query()
        ->select('users.*')
        ->join('students', 'students.user_id', 'users.id')
        ->where('students.classification_id', $model->id)
        ->get();
    } elseif ($model instanceof ClassificationGroup) {
      $users = User::query()
        ->select('users.*')
        ->join('students', 'students.user_id', 'users.id')
        ->join(
          'classifications',
          'classifications.id',
          'students.classification_id'
        )
        ->where('classifications.classification_group_id', $model->id)
        ->get();
    } elseif ($model instanceof Association) {
      $users = User::query()
        ->select('users.*')
        ->join('institution_users', 'institution_users.user_id', 'users.id')
        ->join(
          'user_associations',
          'user_associations.institution_user_id',
          'institution_users.id'
        )
        ->where('user_associations.association_id', $model->id)
        ->get();
    } elseif ($model instanceof Institution) {
      $users = User::query()
        ->join('institution_users', 'institution_users.user_id', 'users.id')
        ->where('institution_users.institution_id', $model->id)
        ->get();
    } elseif ($model instanceof User) {
      $users = collect()->add($model);
    } else {
      return throw new Exception('Invalid messable type');
    }
    if ($forGuardians) {
      $students = Student::query()
        ->whereIn('user_id', $users->map(fn($user) => $user->id)->toArray())
        ->get();
      $guardianStudents = GuardianStudent::query()
        ->whereIn('student_id', $students->map->id)
        ->with('guardian')
        ->get();
      $users = $guardianStudents->map(
        fn($guardianStudent) => $guardianStudent->guardian
      );
    }
    return $users;
  }

  private function submit(
    Collection $receivers,
    $channel,
    ?Model $messageModel
  ): Res {
    if ($receivers->count() < 1) {
      return failRes('No receivers found');
    }

    $res = ApplyMessageCharges::make($this->institution)->run(
      $receivers,
      $channel,
      $messageModel
    );
    if ($res->isNotSuccessful()) {
      return $res;
    }

    if ($channel === NotificationChannelsType::Sms->value) {
      SendBulksms::dispatch(
        $this->message,
        $receivers->join(','),
        $messageModel,
        $this->institution
      );
    } else {
      Mail::to($receivers->toArray())->queue(
        new InstitutionMessageMail(
          $this->institution,
          $this->subject ?? 'Generic Message',
          $this->message,
          $messageModel
        )
      );
    }

    return successRes();
  }

  function applyCharges(
    Collection $receivers,
    $channel,
    Message $messageModel
  ): Res {
    $institutionGroup = $this->institution->institutionGroup;
    $instGroupPriceList = $institutionGroup
      ->pricelists()
      ->where(
        'type',
        $channel === NotificationChannelsType::Sms->value
          ? PriceType::SmsSending->value
          : PriceType::EmailSending->value
      )
      ->first();

    if (!$instGroupPriceList) {
      return failRes('Price List has not been set');
    }

    $amountToPay = $receivers->count() * $instGroupPriceList->amount;

    if ($amountToPay > $institutionGroup->credit_wallet) {
      return failRes('Insufficient wallet balance');
    }

    if ($amountToPay > 0) {
      TransactionHandler::make(
        $this->institution,
        Str::orderedUuid()
      )->deductCreditWallet(
        $amountToPay,
        $messageModel,
        "Sent {$receivers->count()} $channel message(s)"
      );
    }
    return successRes();
  }
}
