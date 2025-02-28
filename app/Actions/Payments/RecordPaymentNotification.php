<?php
namespace App\Actions\Payments;

use App\Enums\NotificationChannelsType;
use App\Enums\NotificationReceiversType;
use App\Enums\SchoolNotificationPurpose;
use App\Jobs\SendBulksms;
use App\Mail\PaymentNotificationMail;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\ReceiptType;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use App\Support\SettingsHandler;
use Illuminate\Support\Facades\DB;
use Mail;

class RecordPaymentNotification
{
  private SettingsHandler $settingsHandler;
  private string $reference;
  /**
   * @param Institution $institution
   * @param array{
   *     reference: string,
   *     receipt_type_id: int,
   *     classification_ids: int[],
   *     channel: string,
   *     receiver: string,
   * } $data
   */
  public function __construct(
    private User $user,
    private array $data,
    private Institution $institution,
    private ReceiptType $receiptType
  ) {
    $this->settingsHandler = SettingsHandler::makeFromRoute();
    $this->reference = $data['reference'];
  }

  public function run()
  {
    $currentTerm = $this->settingsHandler->getCurrentTerm();
    $currentAcademicSessionId = $this->settingsHandler->getCurrentAcademicSession();

    //== Get applicable students. The query below returns the USER data of the students.
    $students = Student::query()
      ->select('students.*')
      ->join(
        'institution_users',
        'students.user_id',
        'institution_users.user_id'
      )
      ->where('institution_users.institution_id', $this->institution->id)
      ->when(
        $this->data['receiver'] ===
          NotificationReceiversType::SpecificClass->value &&
          !empty($this->data['classification_id']),
        fn($query) => $query->whereIn(
          'classification_id',
          $this->data['classification_ids']
        )
      )
      ->with('user', 'classification.institution')
      ->get();

    //== Save to the 'school_notification' :: 'instituion_id', 'purpose (receiptType)', 'sender', 'receiver_type', 'receiver_ids'
    [$morphType, $morphIds] = $this->getReceiverMorph();
    $schoolNotification = SchoolNotification::query()->create([
      'reference' => $this->data['reference'],
      'sender_user_id' => currentUser()->id,
      'receiver_type' => $morphType,
      'receiver_ids' => $morphIds,
      'institution_id' => $this->institution->id,
      'purpose' => SchoolNotificationPurpose::Receipt->value,
      'description' => 'School Fees Receipt'
    ]);

    foreach ($students as $student) {
      $guardian = $student->guardian;
      if ($guardian) {
        $notificationChannel = $this->data['channel'];

        //== Send via Email
        if ($notificationChannel === NotificationChannelsType::Email->value) {
          Mail::to($guardian?->email)->queue(
            new PaymentNotificationMail(
              $student,
              $this->receiptType,
              $schoolNotification
            )
          );
        } elseif (
          $notificationChannel === NotificationChannelsType::Sms->value
        ) {
          SendBulksms::dispatch($student, $this->receiptType);
        } else {
          die('No channel selected');
        }
      }
    }
  }
  function getReceiverMorph()
  {
    if (
      $this->data['receiver'] === NotificationReceiversType::AllClasses->value
    ) {
      return [
        MorphMap::key(ClassificationGroup::class),
        ClassificationGroup::all()->pluck('id')
      ];
    }
    return [
      MorphMap::key(Classification::class),
      $this->data['classification_ids']
    ];
  }
}
