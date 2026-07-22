<?php

namespace App\Models;

use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceList extends BaseModel
{
  use HasFactory;

  protected $table = 'price_lists';
  protected $guarded = [];

  protected $casts = [
    'institution_group_id' => 'integer',
    'payment_structure' => PaymentStructure::class,
    'amount' => 'float',
    'partner_commission' => 'float',
    'type' => PriceType::class
  ];

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }
}
