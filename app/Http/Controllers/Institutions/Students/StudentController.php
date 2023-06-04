<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Actions\Users\DownloadStudentRecordingSheet;
use App\Actions\Users\InsertStudentFromRecordingSheet;
use App\Actions\RecordStudent;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Requests\CreateStudentRequest;
use App\Models\Classification;
use App\Models\Institution;
use App\Rules\ExcelRule;
use App\Support\UITableFilters\StudentUITableFilters;
use Storage;

class StudentController extends Controller
{
  function index(Request $request)
  {
    $query = Student::query()->select('students.*');
    StudentUITableFilters::make($request->all(), $query)->filterQuery();

    return inertia('institutions/students/list-students', [
      'students' => paginateFromRequest(
        $query->with('user', 'classification')->latest('students.id')
      )
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
    RecordStudent::create($request->validated(), $request->classification);

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
    RecordStudent::create($request->validated(), $request->classification);

    return $this->ok();
  }

  public function uploadStudents(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    $request->validate([
      'file' => ['required', 'file', new ExcelRule($this->file('file'))]
    ]);
    InsertStudentFromRecordingSheet::run($request->file, $classification);
    return $this->ok();
  }
}
