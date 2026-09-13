<?php

namespace App\Mail;

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\Message;
use App\Traits\ChargesInstitutionMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class InstitutionMessageMail extends Mailable
{
  use ChargesInstitutionMessage, Queueable, SerializesModels;

  private int $recipientCount;

  private string $chargeReference;

  /**
   * Create a new message instance.
   */
  public function __construct(
    public Institution $institution,
    public string $subjectTitle,
    public string $message,
    private ?Message $messageModel = null,
    ?int $recipientCount = null,
    ?string $chargeReference = null
  ) {
    $this->afterCommit();
    $this->recipientCount = max(1, $recipientCount ?? 1);
    $this->chargeReference = $chargeReference ?? Str::orderedUuid()->toString();
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
   * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
   */
  public function send($mailer)
  {
    $recipientCount = max(1, count($this->to), $this->recipientCount);

    if (
      !$this->chargeInstitutionMessage(
        $this->institution,
        NotificationChannelsType::Email,
        $recipientCount,
        $this->messageModel,
        $this->chargeReference
      )
    ) {
      return null;
    }

    $sentMessage = parent::send($mailer);

    if (!$this->messageModel) {
      return $sentMessage;
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
