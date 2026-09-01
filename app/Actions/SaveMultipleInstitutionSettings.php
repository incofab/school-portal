<?php

namespace App\Actions;

use App\Models\Institution;

class SaveMultipleInstitutionSettings
{
  public static function run(Institution $institution, array $settings): void
  {
    app(self::class)->save($institution, $settings);
  }

  public function save(Institution $institution, array $settings): void
  {
    foreach ($settings as $setting) {
      SaveInstitutionSetting::run($institution, $setting);
    }
  }
}
