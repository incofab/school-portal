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

  private static ?self $instance = null;
  static function makeFromRoute(bool $refresh = false): static
  {
    if (self::$instance && !$refresh) {
      return self::$instance;
    }
    $institutionSettings = currentInstitution()?->institutionSettings ?? [];
    $formatted = [];
    foreach ($institutionSettings as $key => $value) {
      if ($value['type'] === 'array') {
        $value['value'] = json_decode($value->value, true);
      }
      $formatted[$value->key] = $value;
    }
    self::$instance = new self($formatted);
    return self::$instance;
  }

  function get(string $key): InstitutionSetting|null
  {
    return $this->settings[$key] ?? null;
  }

  function getValue(string $key, $default = null)
  {
    return $this->get($key)?->value ?? $default;
  }

  function usesMidTerm()
  {
    return $this->getValue(
      InstitutionSettingType::UsesMidTermResult->value,
      false
    );
  }

  /** Indicates whether the school is currently on Mid or Full term */
  function isOnMidTerm()
  {
    if (!$this->usesMidTerm()) {
      return false;
    }
    return $this->getValue(
      InstitutionSettingType::CurrentlyOnMidTerm->value,
      false
    );
  }

  function getCurrentTerm($default = null)
  {
    if (!$default) {
      $default = TermType::First->value;
    }
    return $this->getValue(InstitutionSettingType::CurrentTerm->value) ??
      $default;
  }

  function getCurrentAcademicSession($default = 'fetch')
  {
    return $this->getValue(
      InstitutionSettingType::CurrentAcademicSession->value
    ) ??
      ($default === 'fetch'
        ? AcademicSession::query()
          ->latest('id')
          ->first()?->id
        : $default);
  }

  function getResultTemplate($default = null)
  {
    if (!$default) {
      $default = ResultTemplateType::Template1->value;
    }
    $resultSetting = $this->getValue(InstitutionSettingType::Result->value);
    return $resultSetting['template'] ?? $default;
    // return $this->getValue(InstitutionSettingType::Result->value) ?? $default;
  }

  function academicQueryData(
    $table = '',
    $academicSessionId = null,
    $term = null,
    $forMidTerm = null
  ) {
    if ($table) {
      $table .= '.';
    }
    return [
      "{$table}academic_session_id" =>
        $academicSessionId ?? $this->getCurrentAcademicSession(),
      "{$table}term" => $term ?? $this->getCurrentTerm(),
      "{$table}for_mid_term" =>
        $forMidTerm === null ? $this->isOnMidTerm() : $forMidTerm
    ];
  }
}
