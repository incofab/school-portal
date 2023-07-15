<?php
namespace App\Support;

use App\Enums\InstitutionSettingType;
use App\Enums\ResultTemplateType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\InstitutionSetting;

class SettingsHandler
{
  function __construct(private array $settings)
  {
  }

  function all()
  {
    return $this->settings;
  }

  static function makeFromRoute(): static
  {
    $institutionSettings = currentInstitution()?->institutionSettings ?? [];
    $formatted = [];
    foreach ($institutionSettings as $key => $value) {
      $formatted[$value->key] = $value;
    }
    return new self($formatted);
  }

  function get(string $key): InstitutionSetting|null
  {
    return $this->settings[$key] ?? null;
  }

  function getValue(string $key, $default = null)
  {
    return $this->get($key)?->value ?? $default;
  }

  function getCurrentTerm($default = null)
  {
    if (!$default) {
      $default = TermType::First->value;
    }
    return $this->getValue(InstitutionSettingType::CurrentTerm->value) ??
      $default;
  }

  function getCurrentAcadenicSession($default = 'fetch')
  {
    if ($default === 'fetch') {
      $default = AcademicSession::query()
        ->latest('id')
        ->first()?->id;
    }
    return $this->getValue(
      InstitutionSettingType::CurrentAcademicSession->value
    ) ?? $default;
  }

  function getResultTemplate($default = null)
  {
    if (!$default) {
      $default = ResultTemplateType::Template1->value;
    }
    return $this->getValue(InstitutionSettingType::ResultTemplate->value) ??
      $default;
  }
}
