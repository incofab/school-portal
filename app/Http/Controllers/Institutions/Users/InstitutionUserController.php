<?php
namespace App\Http\Controllers\Institutions\Users;

use App\Actions\Users\DownloadStaffRecordingSheet;
use App\Actions\Users\InsertStaffFromRecordingSheet;
use App\Actions\RecordStaff;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStaffRequest;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Rules\ExcelRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Storage;

class InstitutionUserController extends Controller
{
  public function create()
  {
    return inertia('institutions/users/create-edit-user');
  }

  public function store(CreateStaffRequest $request)
  {
    RecordStaff::create($request->validated());

    return $this->ok();
  }

  // function edit(Institution $institution, InstitutionUser $editInstitutionUser)
  // {
  //   abort_unless($institution->id == $editInstitutionUser->institution_id, 403);
  //   return inertia('institutions/users/create-edit-user', [
  //     'institutionUser' => $editInstitutionUser->load(['user', 'institution'])
  //   ]);
  // }

  // function update(
  //   CreateStaffRequest $request,
  //   Institution $institution,
  //   InstitutionUser $editInstitutionUser
  // ) {
  //   abort_unless($institution->id == $editInstitutionUser->institution_id, 403);
  //   RecordStaff::create($request->validated());
  //   return $this->ok();
  // }

  public function downloadTemplate()
  {
    $excelWriter = DownloadStaffRecordingSheet::run();
    $filename = 'staff-recording-template.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename);
  }

  public function uploadStaff(Request $request, Institution $institution)
  {
    $request->validate([
      'file' => ['required', 'file', new ExcelRule($request->file('file'))],
      'role' => [
        'required',
        Rule::notIn([
          InstitutionUserType::Student->value,
          InstitutionUserType::Alumni->value
        ])
      ]
    ]);
    InsertStaffFromRecordingSheet::run($request->file, $request->role);
    return $this->ok();
  }
}
