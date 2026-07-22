<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerRegistrationRequest extends BaseModel
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];

  /**
   * Parent partner. Retrieves the partner that referred this applicant
   */
  public function referral()
  {
    return $this->belongsTo(Partner::class, 'referral_id');
  }
}
