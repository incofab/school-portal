<?php

namespace App\Http\Controllers\Institutions\Staff;

use DB;
use App\Models\Student;
use App\Rules\ExcelRule;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Actions\RecordStudent;
use App\Models\Classification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\CreateStudentRequest;
use App\Actions\Users\DownloadStudentRecordingSheet;
use App\Support\UITableFilters\StudentUITableFilters;
use App\Actions\Users\InsertStudentFromRecordingSheet;
use App\Enums\InstitutionUserType;
use Illuminate\Validation\Rule;

class StudentManagementController extends Controller
{
  function index(Request $request, Institution $institution)
  {
    $query = Student::query()->select('students.*');
    StudentUITableFilters::make($request->all(), $query)
      ->filterQuery()
      ->getQuery()
      ->latest('users.last_name');
    $countQuery = Student::query()->joinInstitution($institution->id);

    $studentCount = (clone $countQuery)
      ->where('institution_users.role', InstitutionUserType::Student)
      ->count();
    $alumniCount = (clone $countQuery)
      ->where('institution_users.role', InstitutionUserType::Alumni)
      ->count();
    return inertia('institutions/students/list-students', [
      'students' => paginateFromRequest(
        $query
          ->where('institution_users.role', InstitutionUserType::Student)
          ->with('user', 'classification', 'institutionUser')
          ->latest('students.id')
      ),
      'studentCount' => $studentCount,
      'alumniCount' => $alumniCount
    ]);
  }

  function classStudentsTiles(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    return inertia('institutions/students/class-students-tiles', [
      'students' => $classification
        ->students()
        ->with('user')
        ->get()
    ]);
  }

  function classStudentsIdCards(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    return inertia('institutions/students/class-students-id-cards', [
      'students' => $classification
        ->students()
        ->with('user')
        ->get()
    ]);
  }

  public function create(Institution $institution)
  {
    return inertia('institutions/students/create-edit-student', [
      'classification' => Classification::all()
    ]);
  }

  public function store(Institution $institution, CreateStudentRequest $request)
  {
    RecordStudent::make($institution, $request->validated())->create();

    return $this->ok();
  }

  function edit(Institution $institution, Student $student)
  {
    return inertia('institutions/students/create-edit-student', [
      'student' => $student->load('user.institutionUser', 'classification')
    ]);
  }

  public function downloadTemplate(Institution $institution)
  {
    $excelWriter = DownloadStudentRecordingSheet::run();
    $filename = 'student-recording-template.xlsx';
    $excelWriter->save(storage_path("app/$filename"));

    return Storage::download($filename);
  }

  public function update(
    CreateStudentRequest $request,
    Institution $institution,
    Student $student
  ) {
    RecordStudent::make($institution, $request->validated())->update($student);

    return $this->ok();
  }

  public function uploadStudents(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    $request->validate([
      'file' => ['required', 'file', new ExcelRule($request->file('file'))]
    ]);
    InsertStudentFromRecordingSheet::run(
      $institution,
      $request->file,
      $classification
    );
    return $this->ok();
  }

  public function destroy(
    Request $request,
    Institution $institution,
    Student $student
  ) {
    $currentUser = currentUser();
    abort_unless($currentUser->isInstitutionAdmin(), 403);

    $student->load('institutionUser', 'user');
    $user = $student->user;
    $institutionUser = $student->institutionUser;

    abort_if(
      $student->termResults()->count() > 0,
      403,
      'This student has existing results, move to alumni instead'
    );

    DB::beginTransaction();
    $student->courseResults()->delete();
    $student->termResults()->delete();
    $student->sessionResults()->delete();
    $student->delete();
    $institutionUser->delete();
    if (
      DB::table('institution_users')
        ->where('user_id', $user->id)
        ->where('deleted_at', null)
        ->get()
        ->count() < 1
    ) {
      $user->delete();
    }
    DB::commit();

    return $this->ok();
  }

  function updateCode(
    Request $request,
    Institution $institution,
    Student $student
  ) {
    $data = $request->validate([
      'code' => [
        'required',
        Rule::unique('students', 'code')->ignore($student->id, 'id')
      ]
    ]);
    $student->fill($data)->save();

    return $this->ok();
  }
}
