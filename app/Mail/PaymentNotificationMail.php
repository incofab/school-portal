<?php

namespace App\Mail;

use App\Actions\Fees\GetStudentPendingFees;
use App\Enums\EmailRecipientType;
use App\Enums\EmailStatus;
use App\Models\Email;
use App\Models\EmailRecipient;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\File;

class PaymentNotificationMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public $feesToPay;
  public $totalFeesToPay;
  public User $user;
  public User $guardian;
  public Institution $currentInstitution;

  public function __construct(
    public Student $student,
    public ReceiptType $receiptType,
    private SchoolNotification $schoolNotification
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
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(subject: 'Payment Notification');
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(markdown: 'mail.payment-notification-mail');
  }

  /**
   * @param \Illuminate\Contracts\Mail\Mailer $mailer
   */
  function send($mailer)
  {
    parent::send($mailer);

    //== Save to DB.
    // Render the markdown content to HTML using the Markdown class
    $markdown = new Markdown(view(), config('mail.markdown'));
    $bodyContent = $markdown->renderText('mail.payment-notification-mail', [
      'guardian' => $this->guardian,
      'receiptType' => $this->receiptType,
      'student' => $this->student,
      'feesToPay' => $this->feesToPay,
      'totalFeesToPay' => $this->totalFeesToPay
    ]);

    $data = [
      'institution_id' => $this->currentInstitution->id,
      'sender_user_id' => $this->user->id,
      'subject' => 'Payment Notification',
      'body' => $bodyContent,
      'type' => EmailRecipientType::Single->value,
      'status' => EmailStatus::Sent->value,
      'sent_at' => now(),
      'messageable_type' => $this->schoolNotification->getMorphClass(),
      'messageable_id' => $this->schoolNotification->id,
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

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    return [];
  }
}
