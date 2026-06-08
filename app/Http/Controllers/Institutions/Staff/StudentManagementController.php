<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\RecordStudent;
use App\Actions\Users\DownloadStudentRecordingSheet;
use App\Actions\Users\InsertStudentFromRecordingSheet;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStudentRequest;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Rules\ExcelRule;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\UITableFilters\StudentUITableFilters;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class StudentManagementController extends Controller
{
  public function index(Request $request, Institution $institution)
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
          ->with('user', 'classification', 'institutionUser')
          ->latest('students.id')
      ),
      'studentCount' => $studentCount,
      'alumniCount' => $alumniCount
    ]);
  }

  public function classStudentsTiles(
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

  public function classStudentsIdCards(
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

  public function edit(Institution $institution, Student $student)
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

    $fileName = $request->file('file')->getClientOriginalName();
    app(AcademicActivityLogger::class)->studentBulkUploadStarted(
      $institution,
      $classification,
      $fileName
    );

    try {
      $createdCount = InsertStudentFromRecordingSheet::run(
        $institution,
        $request->file,
        $classification
      );
    } catch (Throwable $throwable) {
      app(AcademicActivityLogger::class)->studentBulkUploadFailed(
        $institution,
        $classification,
        $fileName,
        $throwable
      );

      throw $throwable;
    }

    app(AcademicActivityLogger::class)->studentBulkUploadCompleted(
      $institution,
      $classification,
      $fileName,
      $createdCount
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

  public function updateCode(
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
    $oldCode = $student->code;
    $student->fill($data)->save();

    app(AcademicActivityLogger::class)->studentCodeChanged(
      $institution,
      $student,
      $oldCode,
      $data['code']
    );

    return $this->ok();
  }
}
