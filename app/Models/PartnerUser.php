<?php

namespace App\Models;

use App\Enums\PartnerUserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartnerUser extends BaseModel
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'partner_id' => 'integer',
    'user_id' => 'integer',
    'role' => PartnerUserRole::class
  ];

  public function partner()
  {
    return $this->belongsTo(Partner::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
