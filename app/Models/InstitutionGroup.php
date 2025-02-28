<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionGroup extends Model
{
  use HasFactory;

  public $guarded = [];
  public $casts = [
    'partner_user_id' => 'integer',
    'user_id' => 'integer',
    'credit_wallet' => 'float',
    'debt_wallet' => 'float',
    'loan_limit' => 'float'
  ];

  static function getQueryForManager(User $user)
  {
    return $user->isAdmin()
      ? InstitutionGroup::query()
      : $user->partnerInstitutionGroups();
  }

  function isOwing(): bool
  {
    return $this->debt_wallet > 0;
  }

  function institutions()
  {
    return $this->hasMany(Institution::class);
  }
  function user()
  {
    return $this->belongsTo(User::class);
  }
  function partner()
  {
    return $this->belongsTo(User::class, 'partner_user_id');
  }

  public function fundings()
  {
    return $this->hasMany(Funding::class)->latest();
  }

  public function pricelists()
  {
    return $this->hasMany(PriceList::class);
  }

  public function canGetLoan($amount): bool
  {
    $newDebtBalance = $this->debt_wallet + $amount;
    return $newDebtBalance <= $this->loan_limit;
  }

  public function schemeOfWorks()
  {
    return $this->hasMany(SchemeOfWork::class);
  }
}
