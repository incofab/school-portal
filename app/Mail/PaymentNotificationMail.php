<?php

namespace App\Mail;

use App\Actions\Fees\GetStudentPendingFees;
use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Message;
use App\Models\MessageRecipient;
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
      'recipient_category' => MessageRecipientCategory::Single->value,
      'channel' => NotificationChannelsType::Email->value,
      'status' => MessageStatus::Sent->value,
      'sent_at' => now(),
      'messageable_type' => $this->schoolNotification->getMorphClass(),
      'messageable_id' => $this->schoolNotification->id
    ];
    $message = Message::create($data);

    $data2 = [
      'institution_id' => $this->currentInstitution->id,
      'recipient_contact' => $this->guardian->email,
      'recipient_type' => User::class,
      'recipient_id' => $this->guardian->id,
      'message_id' => $message->id
    ];

    MessageRecipient::create($data2);
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
