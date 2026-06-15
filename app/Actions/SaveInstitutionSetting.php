<?php

namespace App\Actions;

use App\Enums\InstitutionSettingType;
use App\Enums\Media\MediaVisibility;
use App\Enums\ResultExamMode;
use App\Enums\ResultSettingType;
use App\Enums\S3Folder;
use App\Enums\UserFullNameFormat;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Support\Audit\FinancialActivityLogger;
use App\Support\Media\MediaManager;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class SaveInstitutionSetting
{
  public static function run(
    Institution $institution,
    array $data
  ): InstitutionSetting {
    return app(self::class)->save($institution, $data);
  }

  /**
   * @param $institution Institution
   * @param $data array {
   *  key: string,
   *  value: mixed,
   *  photo?: mixed,
   *  type?: string
   * }
   * @return InstitutionSetting
   */
  public function save(
    Institution $institution,
    array $data
  ): InstitutionSetting {
    $this->validateSettingValue($data);

    $rawValue = $data['value'] ?? null;
    $existingSetting = InstitutionSetting::query()
      ->where('institution_id', $institution->id)
      ->where('key', $data['key'])
      ->first();

    $data['value'] =
      Arr::get($data, 'type') === 'array' ? json_encode($rawValue) : $rawValue;

    if (!empty($data['photo'])) {
      $setting = $this->persist($institution, $data);

      $res = app(MediaManager::class)->storeUploadedFile(
        $data['photo'],
        $setting,
        'setting_photo',
        $institution->folder(S3Folder::Settings),
        $institution,
        currentUser(),
        visibility: MediaVisibility::Public,
        meta: ['setting_key' => $data['key']],
        legacyUrlColumn: 'value'
      );
      $data['value'] = $res->media?->url;
    }

    $setting = $this->persist($institution, $data);

    if ($data['key'] === InstitutionSettingType::PaymentKeys->value) {
      app(FinancialActivityLogger::class)->paymentCredentialsChanged(
        $institution,
        $this->arraySettingValue($existingSetting?->value),
        is_array($rawValue) ? $rawValue : $this->arraySettingValue($rawValue)
      );
    }

    return $setting;
  }

  private function persist(
    Institution $institution,
    array $data
  ): InstitutionSetting {
    return InstitutionSetting::query()->updateOrCreate(
      [
        'institution_id' => $institution->id,
        'key' => $data['key']
      ],
      collect($data)
        ->except('photo')
        ->toArray()
    );
  }

  private function arraySettingValue(mixed $value): array
  {
    if (is_array($value)) {
      return $value;
    }

    if (!is_string($value) || $value === '') {
      return [];
    }

    $decoded = json_decode($value, true);

    return is_array($decoded) ? $decoded : [];
  }

  private function validateSettingValue(array $data): void
  {
    if (($data['key'] ?? null) === InstitutionSettingType::Result->value) {
      validator($data, [
        'value' => ['nullable', 'array'],
        'value.' . ResultSettingType::ExamMode->value => [
          'nullable',
          new Enum(ResultExamMode::class)
        ]
      ])->validate();

      return;
    }

    if (
      ($data['key'] ?? null) !==
      InstitutionSettingType::UserFullNameFormat->value
    ) {
      return;
    }

    validator($data, [
      'value' => ['nullable', new Enum(UserFullNameFormat::class)]
    ])->validate();
  }
}
