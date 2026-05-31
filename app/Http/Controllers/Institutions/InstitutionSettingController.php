<?php

namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionSettingType;
use App\Enums\InstitutionUserType;
use App\Enums\Media\MediaVisibility;
use App\Enums\ResultExamMode;
use App\Enums\ResultSettingType;
use App\Enums\S3Folder;
use App\Enums\UserFullNameFormat;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Support\Media\MediaManager;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class InstitutionSettingController extends Controller
{
    public function __construct()
    {
        $this->allowedRoles([InstitutionUserType::Admin]);
    }

    public function index(Institution $institution)
    {
        $query = InstitutionSetting::query();

        return inertia('institutions/settings/list-institution-settings', [
            'institutionSettings' => $query->oldest('key')->get(),
        ]);
    }

    public function search(Institution $institution)
    {
        return response()->json([
            'result' => InstitutionSetting::query()
                ->when(
                    request('search'),
                    fn ($q, $search) => $q->where('key', 'like', "%$search%")
                )
                ->oldest('key')
                ->get(),
        ]);
    }

    public function create(Institution $institution)
    {
        return inertia('institutions/settings/create-edit-institution-settings', [
            'settings' => SettingsHandler::makeFromRoute()->all(),
        ]);
    }

    public function store(Request $request, Institution $institution)
    {
        $data = $request->validate(InstitutionSetting::storeRule());

        $this->saveRecord($institution, $data);

        return $this->ok();
    }

    public function storeMultiple(Institution $institution)
    {
        $data = request()->validate([
            'settings' => ['required', 'array', 'min:1'],
            ...InstitutionSetting::storeRule('settings.'),
        ]);

        foreach ($data as $key => $setting) {
            $this->saveRecord($institution, $setting);
        }

        return $this->ok();
    }

    private function saveRecord(Institution $institution, array $data)
    {
        $this->validateSettingValue($data);

        $rawValue = $data['value'] ?? null;
        $data['value'] =
          Arr::get($data, 'type') === 'array' ? json_encode($rawValue) : $rawValue;

        if (! empty($data['photo'])) {
            $setting = InstitutionSetting::query()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'key' => $data['key'],
                ],
                collect($data)
                    ->except('photo')
                    ->toArray()
            );

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

        InstitutionSetting::query()->updateOrCreate(
            [
                'institution_id' => $institution->id,
                'key' => $data['key'],
            ],
            collect($data)
                ->except('photo')
                ->toArray()
        );
    }

    private function validateSettingValue(array $data): void
    {
        if (($data['key'] ?? null) === InstitutionSettingType::Result->value) {
            validator($data, [
                'value' => ['nullable', 'array'],
                'value.'.ResultSettingType::ExamMode->value => [
                    'nullable',
                    new Enum(ResultExamMode::class),
                ],
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
            'value' => ['nullable', new Enum(UserFullNameFormat::class)],
        ])->validate();
    }
}
