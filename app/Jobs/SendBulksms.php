<?php

namespace App\Jobs;

use App\Actions\Fees\GetStudentPendingFees;
use App\Enums\EmailRecipientType;
use App\Enums\EmailStatus;
use App\Models\Email;
use App\Models\EmailRecipient;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;

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
      "Dear Parent,\nThis is a gentle reminder that the " .
      $this->receiptType->title .
      ' for ' .
      $this->student->user->last_name .
      ' ' .
      $this->student->user->first_name .
      ", is due for payment.\nThe total amount is N" .
      number_format($this->totalFeesToPay) .
      ".\nThank you.";

    $data = [
      'body' => $msg,
      'from' => 'School Mgt',
      'to' => $this->guardian->phone,
      'api_token' => config('services.bulksms_nigeria.api-token'),
      'gateway' => 'direct-refund'
    ];

    $client = new Client();

    $client->request('POST', 'https://www.bulksmsnigeria.com/api/v2/sms', [
      'form_params' => $data
    ]);

    //==Save to Database.
    $data = [
      'institution_id' => $this->currentInstitution->id,
      'sender_user_id' => $this->user->id,
      'subject' => 'Payment Notification',
      'body' => $msg,
      'type' => EmailRecipientType::Single->value,
      'status' => EmailStatus::Sent->value,
      'sent_at' => now()
    ];

    $data2 = [
      'institution_id' => $this->currentInstitution->id,
      'recipient_email' => $this->guardian->email,
      'recipient_type' => User::class,
      'recipient_id' => $this->guardian->id
    ];

    Email::create($data);
    EmailRecipient::create($data2);
  }
}
