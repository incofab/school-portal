<?php
namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionSettingType;
use App\Enums\InstitutionUserType;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Storage;

class InstitutionSettingController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  function index()
  {
    $query = InstitutionSetting::query();
    return inertia('institutions/settings/list-institution-settings', [
      'institutionSettings' => $query->oldest('key')->get()
    ]);
  }

  function search()
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

  function create()
  {
    return inertia('institutions/settings/create-edit-institution-settings', [
      'settings' => SettingsHandler::makeFromRoute()->all()
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'key' => ['required', new Enum(InstitutionSettingType::class)],
      'value' => ['nullable'],
      'photo' => ['nullable', 'image', 'mimes:jpg,png,jpeg', 'max:1024'],
      'display_name' => ['nullable', 'string'],
      'type' => ['nullable', 'string']
    ]);

    $data['value'] =
      $request->type === 'array'
        ? json_encode($request->value)
        : $request->value;

    if ($request->photo) {
      $imagePath = $request->photo->store(
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
    return $this->ok();
  }

  function storeMultiple(Institution $institution)
  {
    $data = request()->validate([
      'settings' => ['required', 'array', 'min:1'],
      'settings.key' => ['required', new Enum(InstitutionSettingType::class)],
      'settings.value' => ['nullable', 'string'],
      'settings.display_name' => ['nullable', 'string'],
      'settings.type' => ['nullable', 'string']
    ]);

    foreach ($data as $key => $setting) {
      InstitutionSetting::query()->updateOrCreate(
        [
          'institution_id' => $institution->id,
          'key' => $setting['key']
        ],
        $setting
      );
    }

    return $this->ok();
  }
}
