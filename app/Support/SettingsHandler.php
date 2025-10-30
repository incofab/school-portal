<?php

namespace App\Support;

use App\DTO\PaymentKeyDto;
use App\Enums\InstitutionSettingType;
use App\Enums\ResultTemplateType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\TermDetail;

class SettingsHandler
{
  function __construct(private array $settings)
  {
  }

  function all()
  {
    return $this->settings;
  }

  // Used in tests
  static function clear()
  {
    self::$instance = null;
  }

  private static ?self $instance = null;
  static function makeFromRoute(bool $refresh = false): static
  {
    if (self::$instance && !$refresh) {
      return self::$instance;
    }
    $institutionSettings = currentInstitution()?->institutionSettings ?? [];
    self::$instance = self::make($institutionSettings);
    return self::$instance;
  }

  static function makeFromInstitution(Institution $institution): static
  {
    $institutionSettings = $institution->institutionSettings ?? [];
    self::$instance = self::make($institutionSettings);
    return self::$instance;
  }

  /**
   * @param \Illuminate\Database\Eloquent\Collection<int, InstitutionSetting>|array $institutionSettings
   */
  static function make($institutionSettings): static
  {
    $formatted = [];
    foreach ($institutionSettings as $key => $value) {
      if ($value['type'] === 'array' && is_string($value->value)) {
        $value['value'] = json_decode($value->value, true);
      }
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

  function usesMidTerm()
  {
    return $this->getValue(
      InstitutionSettingType::UsesMidTermResult->value,
      false
    );
  }

  function resultActivationRequired()
  {
    return boolval(
      $this->getValue(
        InstitutionSettingType::ResultActivationRequired->value,
        true
      )
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

  function getCurrentAcademicSession($default = 'fetch'): int|string|null
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

  function getPaystackKeys(): PaymentKeyDto
  {
    $paymentSetting = $this->getValue(
      InstitutionSettingType::PaymentKeys->value,
      []
    );

    $paystack = $paymentSetting['paystack'] ?? [];
    return new PaymentKeyDto(
      $paystack['public_key'] ?? '',
      $paystack['private_key'] ?? ''
    );
  }

  function fetchCurrentTermDetail(): TermDetail
  {
    $academicSessionId = $this->getCurrentAcademicSession();
    $term = $this->getCurrentTerm();
    abort_unless(
      $academicSessionId && $term,
      401,
      'You need to set the current term and academic session first'
    );
    return TermDetail::query()
      ->with('academicSession')
      ->firstOrCreate([
        'institution_id' => currentInstitution()->id,
        'academic_session_id' => $academicSessionId,
        'term' => $term,
        'for_mid_term' => $this->isOnMidTerm()
      ]);
  }

  function getPinUsageCount()
  {
    return intval(
      $this->getValue(InstitutionSettingType::PinUsageCount->value) ?? 1
    );
  }
}
