<?php
namespace App\Http\Controllers\Institutions\Classifications;

use App\Actions\ClassSheet;
use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Rules\ExcelRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Storage;

class ClassificationController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except([
      'index',
      'search'
    ]);
  }

  function index(Request $request, Institution $institution)
  {
    $query = Classification::query()
      ->when(
        $request->classification_group,
        fn($q, $value) => $q->where('classification_group_id', $value)
      )
      ->with('formTeacher', 'classificationGroup')
      ->withCount('students');
    return inertia('institutions/classifications/list-classifications', [
      'classifications' => paginateFromRequest($query)
    ]);
  }

  function search(Institution $institution)
  {
    return response()->json([
      'result' => Classification::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->orderBy('title')
        ->get()
    ]);
  }

  function create(Institution $institution)
  {
    return inertia('institutions/classifications/create-edit-classification', [
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function multiCreate(Institution $institution)
  {
    return inertia(
      'institutions/classifications/create-multi-classifications',
      [
        'classificationGroups' => ClassificationGroup::all()
      ]
    );
  }

  function multiStore(Institution $institution, Request $request)
  {
    $data = $request->validate(
      Classification::createRule(null, 'classifications.*.')
    );
    info($data);
    foreach ($data['classifications'] as $key => $value) {
      $institution->classifications()->create($value);
    }
    return $this->ok();
  }

  function store(Institution $institution)
  {
    $data = request()->validate(Classification::createRule());
    $institution->classifications()->create($data);
    return $this->ok();
  }

  function edit(Institution $institution, Classification $classification)
  {
    //dd($classification);
    return inertia('institutions/classifications/create-edit-classification', [
      'classification' => $classification->load(
        'formTeacher',
        'classificationGroup'
      ),
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function update(Institution $institution, Classification $classification)
  {
    $data = request()->validate(Classification::createRule($classification));

    $classification->fill($data)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, Classification $classification)
  {
    $numOfStudents = $classification->students()->count();
    abort_unless($numOfStudents > 0, 403, 'This class contains some students');
    $classification->delete();
    return $this->ok();
  }

  function updateStudentStatus(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    $data = $request->validate([
      'status' => ['required', new Enum(InstitutionUserStatus::class)],
      'status_message' => ['nullable', 'string', 'max:255']
    ]);

    $status = InstitutionUserStatus::from($data['status']);
    $studentInstitutionUserIds = $classification
      ->students()
      ->whereNotNull('institution_user_id')
      ->select('institution_user_id');

    $updatedCount = InstitutionUser::query()
      ->whereIn('id', $studentInstitutionUserIds)
      ->update([
        'status' => $status->value,
        'status_message' =>
          $status === InstitutionUserStatus::Suspended
            ? $data['status_message'] ?? null
            : null
      ]);

    return $this->ok([
      'message' => "{$updatedCount} student(s) updated successfully"
    ]);
  }

  public function download(Request $request, Institution $institution)
  {
    $classifications = $institution->classifications()->get();

    $excelWriter = ClassSheet::make($institution)->download($classifications);

    $filename = "{$institution->name}-classes.xlsx";

    $filename = str_replace(['/', ' '], ['_', '-'], $filename);

    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename);
  }

  /** @deprecated */
  public function upload(Request $request, Institution $institution)
  {
    $request->validate([
      'file' => ['required', 'file', new ExcelRule($request->file('file'))]
    ]);
    ClassSheet::make($institution)->upload($request->file);
    return $this->ok();
  }
}
