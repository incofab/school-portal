<?php
namespace App\Http\Controllers\Institutions;

use App\Actions\RecordStaff;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStaffRequest;
use App\Models\Institution;
use App\Models\InstitutionUser;

class InstitutionUserController extends Controller
{
  public function create()
  {
    return inertia('institutions/users/create-edit-user');
  }

  public function store(CreateStaffRequest $request)
  {
    RecordStaff::create($request);

    return $this->ok();
  }

  function edit(Institution $institution, InstitutionUser $institutionUser)
  {
    abort_unless($institution->id === $institutionUser->institution_id, 403);
    return inertia('institutions/users/create-edit-user', [
      'institutionUser' => $institutionUser->load(['user', 'institution'])
    ]);
  }

  function update(
    CreateStaffRequest $request,
    Institution $institution,
    InstitutionUser $institutionUser
  ) {
    abort_unless($institution->id === $institutionUser->institution_id, 403);
    RecordStaff::create($request);
    return $this->ok();
  }

  /*
  function multiStudentCreate($institutionId)
  {
    return $this->view('institution.student.multi-create', [
      'allGrades' => $this->gradeRepository->list($institutionId)['all']
    ]);
  }

  function multiStudentStore($institutionId, Request $request)
  {
    $students = $request->input('students');

    foreach ($students as $student) {
      $ret = Student::insert($student, $request->get('institution'));
    }

    return redirect(route('institution.student.index', [$institutionId]))->with(
      'message',
      'Students registered'
    );
  }

  function edit($institutionId, $tableId)
  {
    $student = Student::whereId($tableId)->firstOrFail();

    return $this->view('institution.student.edit', [
      'data' => $student,
      'allGrades' => $this->gradeRepository->list($institutionId)['all']
    ]);
  }

  function update($institutionId, Request $request, Student $student)
  {
    $student->update($request->all());

    return redirect(route('institution.student.index', $institutionId))->with(
      'message',
      "{$student->firstname}'s record updated"
    );
  }

  function show($institutionId, $table_id_or_studentId)
  {
    $studentData = Student::whereId($table_id_or_studentId)
      ->whereInstitution_id($institutionId)
      ->orWhere('student_id', '=', $table_id_or_studentId)
      ->whereInstitution_id($institutionId)
      ->first();

    return $this->view('institution.student.show', [
      'studentData' => $studentData
    ]);
  }

  function destroy($institutionId, $table_id)
  {
    Student::whereId($table_id)
      ->whereInstitution_id($institutionId)
      ->delete();

    return redirect(route('institution.student.index', $institutionId))->with(
      'message',
      'Student deleted successfully'
    );
  }

  function multiDelete($institutionId, Request $request)
  {
    $studentIDs = explode(',', $request->input('student_id'));

    if (empty($studentIDs)) {
      return redirect(route('institution.student.index', $institutionId))->with(
        'error',
        'No student selected'
      );
    }

    $builder = Student::whereInstitution_id($institutionId)->whereIn(
      'student_id',
      $studentIDs
    );

    $builder->delete();

    return redirect(route('institution.student.index', $institutionId))->with(
      'message',
      'Students deleted'
    );
  }

  function uploadStudentsView($institutionId)
  {
    return $this->view('institution.student.upload', [
      'grades' => Grade::whereInstitution_id($institutionId)->get()
    ]);
  }

  function uploadStudents(
    $institutionId,
    Request $request,
    \App\Helpers\StudentsUploadHelper $studentsUploadHelper
  ) {
    $ret = $studentsUploadHelper->uploadStudent(
      $_FILES,
      $request->get('institution')
    );

    if (!$ret[SUCCESSFUL]) {
      return $this->redirect(redirect()->back(), $ret);
    }

    return redirect(route('institution.student.index', $institutionId))->with(
      'message',
      $ret[MESSAGE]
    );
  }

  function downloadSampleExcel($institutionId)
  {
    $fileToDownload = public_path() . '/student-recording-template.xlsx';
    $file_name = 'student-recording-template.xlsx';

    header('Content-Type: application/zip');

    header("Content-Disposition: attachment; filename=$file_name");

    header('Content-Length: ' . filesize($fileToDownload));

    readfile($fileToDownload);

    exit();
  }
  */
}
