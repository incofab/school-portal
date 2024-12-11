<?php
namespace App\Http\Controllers\Institutions\Classifications;

use App\Actions\ClassSheet;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Rules\ExcelRule;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

  function index(Request $request)
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

  function search()
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

  function create()
  {
    return inertia('institutions/classifications/create-edit-classification', [
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function store(Institution $institution)
  {
    $data = request()->validate([
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classifications', 'title')->where(
          'institution_id',
          $institution->id
        )
      ],
      'description' => ['nullable', 'string'],
      'has_equal_subjects' => ['nullable', 'boolean'],
      'form_teacher_id' => [
        'nullable',
        'integer',
        Rule::exists('institution_users', 'user_id')
          ->where('institution_id', $institution->id)
          ->where('role', InstitutionUserType::Teacher->value)
      ],
      'classification_group_id' => [
        'required',
        new ValidateExistsRule(ClassificationGroup::class)
      ]
    ]);

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
    $data = request()->validate([
      'title' => [
        'required',
        'string',
        'max:100',
        Rule::unique('classifications', 'title')
          ->where('institution_id', $institution->id)
          ->ignore($classification->id, 'id')
      ],
      'description' => ['nullable', 'string'],
      'has_equal_subjects' => ['nullable', 'boolean'],
      'form_teacher_id' => [
        'nullable',
        'integer',
        Rule::exists('institution_users', 'user_id')
          ->where('institution_id', $institution->id)
          ->where('role', InstitutionUserType::Teacher->value)
      ],
      'classification_group_id' => [
        'required',
        new ValidateExistsRule(ClassificationGroup::class)
      ]
    ]);

    $classification->fill($data)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, Classification $classification)
  {
    // $numOfStudents = $classification->students()->count('id');
    // abort_unless($numOfStudents > 0, 403, 'This class contains some students');
    $classification->delete();
    return $this->ok();
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

  public function upload(Request $request, Institution $institution)
  {
    $request->validate([
      'file' => ['required', 'file', new ExcelRule($request->file('file'))]
    ]);
    ClassSheet::make($institution)->upload($request->file);
    return $this->ok();
  }
}
