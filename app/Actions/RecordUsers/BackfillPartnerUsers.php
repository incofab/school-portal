<?php

namespace App\Actions\RecordUsers;

use App\Enums\PartnerUserRole;
use Illuminate\Support\Facades\DB;

class BackfillPartnerUsers
{
  public static function run(): void
  {
    DB::table('partners')
      ->whereNotNull('user_id')
      ->chunkById(100, function ($partners) {
        foreach ($partners as $partner) {
          DB::table('partner_users')->insertOrIgnore([
            'partner_id' => $partner->id,
            'user_id' => $partner->user_id,
            'role' => PartnerUserRole::Admin->value,
            'created_at' => now(),
            'updated_at' => now()
          ]);
        }
      });
  }
}
