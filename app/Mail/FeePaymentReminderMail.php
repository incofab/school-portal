<?php

namespace App\Mail;

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use App\Traits\ChargesInstitutionMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class FeePaymentReminderMail extends Mailable implements ShouldQueue
{
  use ChargesInstitutionMessage, Queueable, SerializesModels;

  public Institution $institution;

  private string $chargeReference;

  public function __construct(
    public Student $student,
    public User $guardian,
    public Fee $fee,
    private int $messageId,
    ?string $chargeReference = null
  ) {
    $this->afterCommit();
    $this->institution = $fee->institution;
    $this->guardian = $student->guardian;
    $this->chargeReference = $chargeReference ?? Str::orderedUuid()->toString();
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
   * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
   */
  public function send($mailer)
  {
    $messageModel = Message::find($this->messageId);

    if (
      !$this->chargeInstitutionMessage(
        $this->institution,
        NotificationChannelsType::Email,
        1,
        $messageModel,
        $this->chargeReference
      )
    ) {
      return null;
    }

    $sentMessage = parent::send($mailer);

    // Render the markdown content to HTML using the Markdown class
    $markdown = new Markdown(view(), config('mail.markdown'));

    if (!$messageModel || $messageModel->body) {
      return $sentMessage;
    }

    $bodyContent = $markdown->renderText('mail.fee-payment-reminder-mail', [
      'fee' => $this->fee,
      'guardian' => $this->guardian,
      'student' => $this->student,
      'institution' => $this->institution
    ]);

    $messageModel
      ->fill([
        'body' => $bodyContent,
        'sent_at' => now(),
        'status' => MessageStatus::Sent->value
      ])
      ->save();

    return $sentMessage;
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
