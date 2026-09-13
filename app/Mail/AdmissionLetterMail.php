<?php

namespace App\Mail;

use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\Message;
use App\Models\User;
use App\Traits\ChargesInstitutionMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class AdmissionLetterMail extends Mailable
{
  use ChargesInstitutionMessage, Queueable, SerializesModels;

  private string $chargeReference;

  public function __construct(
    public Institution $institution,
    public User $user,
    public $url,
    private ?Message $messageModel = null,
    ?string $chargeReference = null
  ) {
    $this->afterCommit();
    $this->chargeReference = $chargeReference ?? Str::orderedUuid()->toString();
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(subject: 'Offer of Provisional Admission');
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(markdown: 'mail.admission-letter-mail');
  }

  /**
   * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
   */
  public function send($mailer)
  {
    if (
      !$this->chargeInstitutionMessage(
        $this->institution,
        NotificationChannelsType::Email,
        1,
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
    $bodyContent = $markdown->renderText('mail.admission-letter-mail', [
      'user' => $this->user,
      'url' => $this->url,
      'institution' => $this->institution
    ]);

    $this->messageModel
      ->fill(['body' => $bodyContent, 'sent_at' => now()])
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
