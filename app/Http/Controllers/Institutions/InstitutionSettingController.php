<?php
namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionUserType;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Storage;

class InstitutionSettingController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  function index(Institution $institution)
  {
    $query = InstitutionSetting::query();
    return inertia('institutions/settings/list-institution-settings', [
      'institutionSettings' => $query->oldest('key')->get()
    ]);
  }

  function search(Institution $institution)
  {
    return response()->json([
      'result' => InstitutionSetting::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('key', 'like', "%$search%")
        )
        ->oldest('key')
        ->get()
    ]);
  }

  function create(Institution $institution)
  {
    return inertia('institutions/settings/create-edit-institution-settings', [
      'settings' => SettingsHandler::makeFromRoute()->all()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate(InstitutionSetting::storeRule());

    $this->saveRecord($institution, $data);
    return $this->ok();
  }

  function storeMultiple(Institution $institution)
  {
    $data = request()->validate([
      'settings' => ['required', 'array', 'min:1'],
      ...InstitutionSetting::storeRule('settings.')
    ]);

    foreach ($data as $key => $setting) {
      $this->saveRecord($institution, $setting);
    }

    return $this->ok();
  }

  private function saveRecord(Institution $institution, array $data)
  {
    $rawValue = $data['value'] ?? null;
    $data['value'] =
      Arr::get($data, 'type') === 'array' ? json_encode($rawValue) : $rawValue;

    if (!empty($data['photo'])) {
      $imagePath = $data['photo']->store(
        $institution->folder(S3Folder::Settings),
        's3_public'
      );
      $publicUrl = Storage::disk('s3_public')->url($imagePath);
      $data['value'] = $publicUrl;
    }

    InstitutionSetting::query()->updateOrCreate(
      [
        'institution_id' => $institution->id,
        'key' => $data['key']
      ],
      collect($data)
        ->except('photo')
        ->toArray()
    );
  }
}
