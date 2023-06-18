<?php

namespace App\Mail;

use App\Models\Institution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

abstract class InstitutionEmail extends Mailable
{
  use Queueable, SerializesModels;
  public Institution $institution;
  /**
   * Create a new message instance.
   */
  public function __construct()
  {
    $this->institution = currentInstitution();
  }
}
