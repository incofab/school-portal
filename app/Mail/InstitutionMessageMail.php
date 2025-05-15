<?php

namespace App\Mail;

use App\Enums\MessageStatus;
use App\Models\Institution;
use App\Models\Message;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;

class InstitutionMessageMail extends Mailable
{
  /**
   * Create a new message instance.
   */
  public function __construct(
    public Institution $institution,
    public string $subjectTitle,
    public string $message,
    private ?Message $messageModel = null
  ) {
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(subject: $this->subjectTitle);
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(markdown: 'mail.institution-message');
  }

  /**
   * @param \Illuminate\Contracts\Mail\Mailer $mailer
   */
  function send($mailer)
  {
    parent::send($mailer);

    if (!$this->messageModel) {
      return;
    }

    $markdown = new Markdown(view(), config('mail.markdown'));
    $bodyContent = $markdown->renderText('mail.institution-message', [
      'subjectTitle' => $this->subjectTitle,
      'message' => $this->message,
      'institution' => $this->institution
    ]);

    $this->messageModel
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
