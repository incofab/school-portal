<?php

namespace App\Mail;

use App\Models\Institution;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Markdown;
use Illuminate\Queue\SerializesModels;

class AdmissionLetterMail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(
    public Institution $institution,
    public User $user,
    public $url,
    private ?Message $messageModel = null
  ) {
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
   * @param \Illuminate\Contracts\Mail\Mailer $mailer
   */
  function send($mailer)
  {
    parent::send($mailer);

    if (!$this->messageModel) {
      return;
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
