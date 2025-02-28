<?php

namespace App\Jobs;

use App\Actions\Fees\GetStudentPendingFees;
use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Http;

class SendBulksms implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $feesToPay;
  public $totalFeesToPay;
  public User $user;
  public User $guardian;
  public Institution $currentInstitution;

  public function __construct(
    public Student $student,
    public ReceiptType $receiptType
  ) {
    $this->user = currentUser();
    $this->currentInstitution = $student->classification->institution;
    $this->guardian = $student->guardian;

    [$this->feesToPay, $this->totalFeesToPay] = (new GetStudentPendingFees(
      $receiptType,
      $student
    ))->run();
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $msg =
      "Dear Parent,\nThis is a gentle reminder that the 
      {$this->receiptType->title} for {$this->student->user->last_name}
      {$this->student->user->first_name}
      , is due for payment.\nThe total amount is N" .
      number_format($this->totalFeesToPay) .
      ".\nThank you.";

    $data = [
      'body' => $msg,
      'from' => 'School Mgt',
      'to' => $this->guardian->phone,
      'api_token' => config('services.bulksms_nigeria.api-token'),
      'gateway' => 'direct-refund'
    ];

    Http::post('https://www.bulksmsnigeria.com/api/v2/sms', [
      'form_params' => $data
    ]);

    //==Save to Database.
    $data = [
      'institution_id' => $this->currentInstitution->id,
      'sender_user_id' => $this->user->id,
      'subject' => 'Payment Notification',
      'body' => $msg,
      'recipient_category' => MessageRecipientCategory::Single->value,
      'channel' => NotificationChannelsType::Sms,
      'status' => MessageStatus::Sent->value,
      'sent_at' => now()
    ];
    $message = Message::create($data);

    $data2 = [
      'institution_id' => $this->currentInstitution->id,
      'recipient_contact' => $this->guardian->phone,
      'recipient_type' => $this->guardian->getMorphClass(),
      'recipient_id' => $this->guardian->id,
      'message_id' => $message->id
    ];

    MessageRecipient::create($data2);
  }
}
