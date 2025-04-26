<?php

namespace App\Mail;

use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Markdown;

class FeePaymentReminderMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public Institution $institution;

  public function __construct(
    public Student $student,
    public User $guardian,
    public Fee $fee,
    private SchoolNotification $schoolNotification
  ) {
    $this->institution = $fee->institution;
    $this->guardian = $student->guardian;
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(subject: "{$this->fee->title} Payment Reminder");
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(markdown: 'mail.fee-payment-reminder-mail');
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
    $bodyContent = $markdown->renderText('mail.fee-payment-reminder-mail', [
      'fee' => $this->fee,
      'guardian' => $this->guardian,
      'student' => $this->student,
      'schoolNotification' => $this->schoolNotification,
      'institution' => $this->institution
    ]);

    $data = [
      'institution_id' => $this->institution->id,
      'sender_user_id' => $this->institution->user_id,
      'subject' => 'Payment Reminer',
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
      'institution_id' => $this->institution->id,
      'recipient_contact' => $this->guardian->email,
      'recipient_type' => $this->guardian->getMorphClass(),
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
