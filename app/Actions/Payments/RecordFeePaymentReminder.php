<?php
namespace App\Actions\Payments;

use App\Actions\Fees\FeeMembersHandler;
use App\Enums\NotificationChannelsType;
use App\Enums\SchoolNotificationPurpose;
use App\Jobs\SendBulksms;
use App\Mail\FeePaymentReminderMail;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\SchoolNotification;
use App\Models\User;
use App\Support\MorphMap;
use Exception;
use Mail;

class RecordFeePaymentReminder
{
  private string $reference;
  /**
   * @param Institution $institution
   * @param array{
   *     reference: string,
   *     message?: string,
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
    $fee->load('feePayments', 'academicSession');
  }

  public function run()
  {
    $users = (new FeeMembersHandler($this->institution))->getFeeMembers(
      $this->fee,
      true
    );

    $receiverIds = $users
      ->map(fn(User $user) => $user->student?->guardian?->id)
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

    foreach ($users as $user) {
      $guardian = $user->student?->guardian;
      if (!$guardian) {
        continue;
      }
      $notificationChannel = $this->data['channel'];

      //== Send via Message
      if ($notificationChannel === NotificationChannelsType::Email->value) {
        if (!$guardian->email) {
          continue;
        }
        Mail::to($guardian->email)->queue(
          new FeePaymentReminderMail(
            $user->student,
            $guardian,
            $this->fee,
            $schoolNotification
          )
        );
      } elseif ($notificationChannel === NotificationChannelsType::Sms->value) {
        if (!$guardian->phone) {
          continue;
        }
        SendBulksms::dispatch($user->student, $guardian, $this->fee);
      } else {
        throw new Exception('No channel selected');
      }
    }
  }
}
