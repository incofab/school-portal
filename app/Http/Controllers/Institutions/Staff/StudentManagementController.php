<?php

namespace App\Http\Controllers\Institutions\Staff;

use DB;
use App\Models\Student;
use App\Rules\ExcelRule;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Actions\RecordStudent;
use App\Models\Classification;
use App\Models\InstitutionUser;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\CreateStudentRequest;
use App\Actions\Users\DownloadStudentRecordingSheet;
use App\Support\UITableFilters\StudentUITableFilters;
use App\Actions\Users\InsertStudentFromRecordingSheet;

class StudentManagementController extends Controller
{
  function index(Request $request)
  {
    $query = Student::query()->select('students.*');
    StudentUITableFilters::make($request->all(), $query)
      ->filterQuery()
      ->joinUser()
      ->getQuery()
      ->latest('users.last_name');

    return inertia('institutions/students/list-students', [
      'students' => paginateFromRequest(
        $query->with('user', 'classification')->latest('students.id')
      )
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

  public function create()
  {
    return inertia('institutions/students/create-edit-student', [
      'classification' => Classification::all()
    ]);
  }

  public function store(CreateStudentRequest $request)
  {
    RecordStudent::make($request->validated())->create();

    return $this->ok();
  }

  function edit(Institution $institution, Student $student)
  {
    return inertia('institutions/students/create-edit-student', [
      'student' => $student->load('user.institutionUser', 'classification')
    ]);
  }

  public function downloadTemplate()
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
    RecordStudent::make($request->validated())->update($student);

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
    InsertStudentFromRecordingSheet::run($request->file, $classification);
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
      $user
        ->institutionUsers()
        ->get()
        ->count() < 1
    ) {
      $user->delete();
    }
    DB::commit();

    return $this->ok();
  }
}
