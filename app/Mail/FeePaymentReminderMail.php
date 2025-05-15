<?php

namespace App\Mail;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\Fee;
use App\Models\Institution;
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
    private int $messageId
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

    // Render the markdown content to HTML using the Markdown class
    $markdown = new Markdown(view(), config('mail.markdown'));

    $messageModel = Message::find($this->messageId);
    if ($messageModel->body) {
      return;
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
