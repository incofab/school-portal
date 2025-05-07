<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Actions\Users\DownloadStaffRecordingSheet;
use App\Actions\Users\InsertStaffFromRecordingSheet;
use App\Actions\RecordStaff;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStaffRequest;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Rules\ExcelRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Storage;

class InstitutionUserController extends Controller
{
  public function create(Institution $institution)
  {
    return inertia('institutions/users/create-edit-user');
  }

  public function store(Institution $institution, CreateStaffRequest $request)
  {
    RecordStaff::make($institution, $request->validated())->create();

    return $this->ok();
  }

  public function downloadTemplate(Institution $institution)
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
    InsertStaffFromRecordingSheet::run(
      $institution,
      $request->file,
      $request->role
    );
    return $this->ok();
  }

  function idCards(
    Institution $institution,
    ?Classification $classification = null
  ) {
    if (!empty($classification)) {
      //Returns Students
      $persons = $classification
        ->students()
        ->with('user')
        ->get();
    } else {
      //Returns Staff
      $persons = InstitutionUser::whereNotIn('role', [
        InstitutionUserType::Student->value,
        InstitutionUserType::Alumni->value
      ])
        ->with('user')
        ->get();
    }

    return inertia('institutions/students/staff-id-cards', [
      'persons' => $persons
    ]);
  }
}
