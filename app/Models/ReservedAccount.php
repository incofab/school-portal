<?php
namespace App\Models;

use App\Core\MonnifyHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservedAccount extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  protected $casts = [
    'reservable_id' => 'integer'
  ];

  // User | InstitutionGroup
  public function reservable()
  {
    return $this->morphTo('reservable');
  }

  static function getReservedAccounts(User $reservable, $fetch = true)
  {
    $reservedAccounts = $reservable->reservedAccounts()->get();
    if (
      $reservedAccounts->isEmpty() &&
      $fetch &&
      ($reservable->bvn || $reservable->nin)
    ) {
      MonnifyHelper::make()->reserveAccount($reservable);
      $reservedAccounts = $reservable->reservedAccounts()->get();
    }
    return $reservedAccounts;
  }
}
