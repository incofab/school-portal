<?php

namespace App\Support;

use App\DTO\PaymentKeyDto;
use App\Enums\InstitutionSettingType;
use App\Enums\ResultExamMode;
use App\Enums\ResultSettingType;
use App\Enums\ResultTemplateType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\TermDetail;

class SettingsHandler
{
  public function __construct(private array $settings)
  {
  }

  public function all()
  {
    return $this->settings;
  }

  // Used in tests
  public static function clear()
  {
    self::$instance = null;
  }

  private static ?self $instance = null;

  public static function makeFromRoute(bool $refresh = false): static
  {
    if (self::$instance && !$refresh) {
      return self::$instance;
    }
    $institutionSettings = currentInstitution()?->institutionSettings ?? [];
    self::$instance = self::make($institutionSettings);

    return self::$instance;
  }

  public static function makeFromInstitution(Institution $institution): static
  {
    $institutionSettings = $institution->institutionSettings ?? [];
    self::$instance = self::make($institutionSettings);

    return self::$instance;
  }

  /**
   * @param  \Illuminate\Database\Eloquent\Collection<int, InstitutionSetting>|array  $institutionSettings
   */
  public static function make($institutionSettings): static
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

  public function get(string $key): ?InstitutionSetting
  {
    return $this->settings[$key] ?? null;
  }

  public function getValue(string $key, $default = null)
  {
    return $this->get($key)?->value ?? $default;
  }

  public function usesMidTerm()
  {
    return $this->getValue(
      InstitutionSettingType::UsesMidTermResult->value,
      false
    );
  }

  public function resultActivationRequired()
  {
    return boolval(
      $this->getValue(
        InstitutionSettingType::ResultActivationRequired->value,
        true
      )
    );
  }

  /** Indicates whether the school is currently on Mid or Full term */
  public function isOnMidTerm()
  {
    if (!$this->usesMidTerm()) {
      return false;
    }

    return $this->getValue(
      InstitutionSettingType::CurrentlyOnMidTerm->value,
      false
    );
  }

  public function getCurrentTerm($default = null)
  {
    if (!$default) {
      $default = TermType::First->value;
    }

    return $this->getValue(InstitutionSettingType::CurrentTerm->value) ??
      $default;
  }

  public function getCurrentAcademicSession($default = 'fetch'): int|string|null
  {
    return $this->getValue(
      InstitutionSettingType::CurrentAcademicSession->value
    ) ??
      ($default === 'fetch'
        ? AcademicSession::query()
          ->orderByDesc('is_active')
          ->latest('id')
          ->first()?->id
        : $default);
  }

  public function getResultTemplate($default = null)
  {
    if (!$default) {
      $default = ResultTemplateType::Template1->value;
    }
    $resultSetting = $this->getValue(InstitutionSettingType::Result->value);

    return $resultSetting['template'] ?? $default;
    // return $this->getValue(InstitutionSettingType::Result->value) ?? $default;
  }

  public function getResultExamMode(): string
  {
    $resultSetting = $this->getValue(InstitutionSettingType::Result->value, []);

    return $resultSetting[ResultSettingType::ExamMode->value] ??
      ResultExamMode::Both->value;
  }

  public function shouldDisplayExamResults(
    ?TermDetail $termDetail,
    bool $forMidTerm
  ): bool {
    $mode = $termDetail?->result_exam_mode ?: $this->getResultExamMode();
    if ($mode instanceof ResultExamMode) {
      $mode = $mode->value;
    }

    return match ($mode) {
      ResultExamMode::None->value => false,
      ResultExamMode::MidTerm->value => $forMidTerm,
      ResultExamMode::FullTerm->value => !$forMidTerm,
      default => true
    };
  }

  public function academicQueryData(
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

  public function getPaystackKeys(): PaymentKeyDto
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

  public function fetchCurrentTermDetail(): TermDetail
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
      ], [
        'inactive_weekdays' => [5, 6]
      ]);
  }

  public function getPinUsageCount()
  {
    return intval(
      $this->getValue(InstitutionSettingType::PinUsageCount->value) ?? 1
    );
  }

  public function getUserFullNameFormat(): ?string
  {
    $format = $this->getValue(
      InstitutionSettingType::UserFullNameFormat->value
    );

    return filled($format) ? $format : null;
  }
}
