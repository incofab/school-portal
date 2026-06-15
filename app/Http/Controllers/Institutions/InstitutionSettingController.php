<?php

namespace App\Http\Controllers\Institutions;

use App\Actions\SaveInstitutionSetting;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;

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
      'institutionSettings' => $query->oldest('key')->get()
    ]);
  }

  public function search(Institution $institution)
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

  public function create(Institution $institution)
  {
    return inertia('institutions/settings/create-edit-institution-settings', [
      'settings' => SettingsHandler::makeFromRoute()->all()
    ]);
  }

  public function store(Request $request, Institution $institution)
  {
    $data = $request->validate(InstitutionSetting::storeRule());

    SaveInstitutionSetting::run($institution, $data);

    return $this->ok();
  }

  public function storeMultiple(Institution $institution)
  {
    $data = request()->validate([
      'settings' => ['required', 'array', 'min:1'],
      ...InstitutionSetting::storeRule('settings.*.')
    ]);

    foreach ($data['settings'] as $setting) {
      SaveInstitutionSetting::run($institution, $setting);
    }

    return $this->ok();
  }
}
