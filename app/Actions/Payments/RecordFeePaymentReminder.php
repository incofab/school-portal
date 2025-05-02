<?php
namespace App\Actions\Payments;

use App\Actions\Fees\FeeMembersHandler;
use App\Actions\Messages\ApplyMessageCharges;
use App\Actions\Messages\RecordMessage;
use App\Enums\NotificationChannelsType;
use App\Enums\SchoolNotificationPurpose;
use App\Jobs\SendBulksms;
use App\Mail\FeePaymentReminderMail;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Message;
use App\Models\SchoolNotification;
use App\Models\User;
use App\Support\MorphMap;
use App\Support\Res;
use Exception;
use Illuminate\Support\Collection;
use Mail;

class RecordFeePaymentReminder
{
  private string $reference;
  private string $notificationChannel;
  /**
   * @param Institution $institution
   * @param array{
   *     reference: string,
   *     channel: string,
   * } $data
   */
  public function __construct(
    private User $user,
    private array $data,
    private Institution $institution,
    private Fee $fee
  ) {
    $this->reference = $data['reference'];
    $this->notificationChannel = $this->data['channel'];
    $fee->load('feePayments', 'academicSession');
  }

  public function run(): Res
  {
    $users = (new FeeMembersHandler($this->institution))->getFeeMembers(
      $this->fee,
      true
    );
    $users = $users->filter(
      fn($user) => $this->notificationChannel ===
      NotificationChannelsType::Email->value
        ? $user->student?->guardian?->email
        : $user->student?->guardian?->phone
    );

    if ($users->isEmpty()) {
      return failRes('No guardians found');
    }

    $receiverIds = $users
      ->map(fn(User $user) => $user->student->guardian->id)
      ->toArray();

    $schoolNotification = SchoolNotification::query()->create([
      'reference' => $this->reference,
      'sender_user_id' => $this->user->id,
      'receiver_type' => MorphMap::key(User::class),
      'receiver_ids' => $receiverIds,
      'institution_id' => $this->institution->id,
      'purpose' => SchoolNotificationPurpose::Receipt->value,
      'description' => "Reminder: {$this->fee->title}"
    ]);

    $messageModel = $this->saveMessage($users, $schoolNotification);

    $res = ApplyMessageCharges::make($this->institution)->run(
      $users,
      $this->notificationChannel,
      $messageModel
    );
    if ($res->isNotSuccessful()) {
      return $res;
    }

    foreach ($users as $user) {
      $guardian = $user->student->guardian;
      $this->dispatchMessage($guardian, $user, $messageModel);
    }
    return successRes();
  }

  private function dispatchMessage(
    User $guardian,
    User $user,
    Message $messageModel
  ) {
    if ($this->notificationChannel === NotificationChannelsType::Email->value) {
      // info("Running => {$user->id}");
      Mail::to($guardian->email)->queue(
        new FeePaymentReminderMail(
          $user->student,
          $guardian,
          $this->fee,
          $messageModel->id
        )
      );
    } elseif (
      $this->notificationChannel === NotificationChannelsType::Sms->value
    ) {
      SendBulksms::dispatch(
        $this->getSmsMessage($user),
        $guardian->phone,
        $messageModel,
        $this->institution
      );
    } else {
      throw new Exception('No channel selected');
    }
  }

  private function getSmsMessage(User $user)
  {
    return "Dear Parent,\nThis is a gentle reminder that the
      {$this->fee->title} for {$user->last_name}
      {$user->first_name}, is due for payment.\nThe total amount is N" .
      number_format($this->fee->amount) .
      ".\nThank you.";
  }

  private function saveMessage(
    Collection $receivers,
    SchoolNotification $schoolNotification
  ) {
    $recordMessage = new RecordMessage($this->institution, $this->user, [
      'subject' => 'Fee Payment Reminder',
      'body' => '',
      'channel' => $this->notificationChannel
    ]);

    $contacts = $receivers->map(
      fn($user) => $this->notificationChannel ===
      NotificationChannelsType::Email->value
        ? $user->student->guardian->email
        : $user->student->guardian->phone
    );

    if ($contacts->count() < 2) {
      $messageModel = $recordMessage
        ->forSingle($contacts->first())
        ->save($schoolNotification);
    } else {
      $messageModel = $recordMessage
        ->forMultiple($contacts->toArray())
        ->save($schoolNotification);
    }
    return $messageModel;
  }
}
