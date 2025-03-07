<?php

namespace App\Http\Controllers\Institutions\PaymentNotifications;

use App\Actions\Payments\RecordPaymentNotification;
use App\Enums\InstitutionUserType;
use App\Enums\NotificationChannelsType;
use App\Enums\NotificationReceiversType;
use App\Enums\PaymentInterval;
use App\Enums\SchoolNotificationPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentNotificationRequest;
use App\Jobs\SendBulksms;
use App\Mail\PaymentNotificationMail;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Rules\ValidateExistsRule;
use App\Rules\ValidateUniqueRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class PaymentNotificationController extends Controller
{
  private SettingsHandler $settingHandler;

  function __construct(protected Institution $institution)
  {
    $this->allowedRoles([
      InstitutionUserType::Admin
    ])->except(['index', 'search', 'show']);

    $this->settingHandler = SettingsHandler::makeFromRoute();
  }

  //
  public function index()
  {
  }

  public function create()
  {
    return inertia('institutions/payment-notifications/create-notification', [
      'receiptTypes' => ReceiptType::all(),
      'classification' => Classification::all()
    ]);
  }

  public function store(
    Institution $institution,
    PaymentNotificationRequest $request
  ) {
    // $data = $request->validated();
    $receiptTypeExists = new ValidateExistsRule(ReceiptType::class);
    $data = $request->validate([
      'receipt_type_id' => ['required', $receiptTypeExists],
      'reference' => [
        'required',
        new ValidateUniqueRule(SchoolNotification::class)
      ],
      'receiver' => ['required', new Enum(NotificationReceiversType::class)],
      'classification_ids' => ['nullable'],
      'classification_ids.*' => [
        'nullable',
        'integer',
        'exists:classifications,id',
        Rule::exists('classifications', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'channel' => ['required', new Enum(NotificationChannelsType::class)]
    ]);

    (new RecordPaymentNotification(
      currentUser(),
      $data,
      $institution,
      $receiptTypeExists->getModel()
    ))->run();
    /* 
    $currentTerm = $this->settingHandler->getCurrentTerm();
    $currentAcademicSessionId = $this->settingHandler->getCurrentAcademicSession();

    $receiptType = ReceiptType::findOrFail($data['receipt_type_id']);

    //== Get applicable students. The query below returns the USER data of the students.
    $students = Student::query()
      ->select('students.*')
      ->join(
        'institution_users',
        'students.user_id',
        'institution_users.user_id'
      )
      ->where('institution_users.institution_id', $institution->id)
      ->when(
        $data['receiver'] === NotificationReceiversType::SpecificClass->value &&
          !empty($data['classification_id']),
        fn($query) => $query->whereIn(
          'classification_id',
          $data['classification_ids']
        )
      )
      ->with('user', 'classification.institution')
      ->get();

      //== Save to the 'school_notification' :: 'instituion_id', 'purpose (receiptType)', 'sender', 'receiver_type', 'receiver_ids'

          SchoolNotification::query()->create([
            'reference' => $data['reference'],
           'sender' => currentUser()->id,
           'receiver_type' => $data['receiver'],
           'receiver_ids' => $data['receiver'] === NotificationReceiversType::SpecificClass->value? $data['classification_ids'] : [$student->id],
            'institution_id' => $institution->id,
            'purpose' => SchoolNotificationPurpose::Receipt->value,
          ]);

    foreach ($students as $student) {
      $guardian = $student->guardian;
      if ($guardian) {
        $notificationChannel = $data['channel'];

        //== Send via Email
        if ($notificationChannel === NotificationChannelsType::Email->value) {
          Mail::to($guardian?->email)->queue(
            new PaymentNotificationMail($student, $receiptType)
          );

          // Mail::to($guardian?->email)->send(
          //   new PaymentNotificationMail($student, $receiptType)
          // );
        }

        //== Send vis BulkSMS
        if ($notificationChannel === NotificationChannelsType::Sms->value) {
          SendBulksms::dispatch($student, $receiptType);
        }
      }
    }
*/
    return $this->ok();
  }
}
