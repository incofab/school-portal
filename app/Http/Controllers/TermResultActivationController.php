<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Pin;
use App\Models\Student;
use App\Models\TermResult;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use URL;

class TermResultActivationController extends Controller
{
  public function create()
  {
    return inertia('activate-student-term-result');
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'student_code' => ['required', 'exists:students,code'],
      'pin' => ['required'],
      'term_result_id' => ['nullable']
    ]);

    $pin = Pin::query()
      ->where('pin', $data['pin'])
      ->with('institution')
      ->first();

    if (!$pin) {
      throw ValidationException::withMessages(['pin' => 'Invalid pin']);
    }
    $institution = $pin->institution;

    $student = Student::query()
      ->select('students.*')
      // ->forInstitution($pin->institution_id)
      ->where('students.code', $data['student_code'])
      ->with('user', 'institutionUser.institution')
      ->firstOrFail();

    if (
      $institution->institution_group_id ===
      $student->institutionUser->institution->institution_group_id
    ) {
      throw ValidationException::withMessages([
        'pin' => 'This pin is invalid'
      ]);
    }

    if ($pin->student_id && $pin->student_id !== $student->id) {
      throw ValidationException::withMessages([
        'pin' => 'This Pin is not for you'
      ]);
    }

    $termResults = TermResult::query()
      ->where('institution_id', $pin->institution_id)
      ->where('student_id', $student->id)
      ->when(
        $request->term_result_id,
        fn($q, $value) => $q->where('id', $value)
      )
      ->activated(false)
      ->with('classification', 'academicSession')
      ->get();

    $count = $termResults->count();
    if ($count === 0) {
      return $this->errorRes(
        'Seems results have already been activate, Login to access them',
        route('student-login')
      );
    }

    if ($count === 1) {
      return $this->activateResult($termResults->first(), $pin, $student);
    }

    return response()->json([
      'has_multiple_results' => true,
      'term_results' => $termResults
    ]);
  }

  private function successRes(Institution $institution, TermResult $termResult)
  {
    // $route = route('institutions.students.result-sheet', [
    //   $institution->uuid,
    //   $termResult->student_id,
    //   $termResult->classification_id,
    //   $termResult->academic_session_id,
    //   $termResult->term,
    //   $termResult->for_mid_term ? 1 : 0
    // ]);
    $route = URL::temporarySignedRoute(
      'institutions.students.result-sheet.signed',
      now()->addMinutes(30),
      [
        $institution->uuid,
        $termResult->student_id,
        $termResult->classification_id,
        $termResult->academic_session_id,
        $termResult->term,
        $termResult->for_mid_term ? 1 : 0
      ]
    );
    return response()->json(['redirect_url' => $route, 'activated' => true]);
  }

  private function errorRes($message, $redirectUrl = null)
  {
    return response()->json([
      'redirect_url' => $redirectUrl,
      'message' => $message
    ]);
  }

  private function activateResult(
    TermResult $termResult,
    Pin $pin,
    Student $student
  ) {
    if (!$this->canActivate($pin, $termResult)) {
      throw ValidationException::withMessages([
        'pin' => 'Invalid pin combination'
      ]);
    }
    $termResult->fill(['is_activated' => true])->save();
    if (!$pin->term_result_id) {
      $pin
        ->fill([
          'term_result_id' => $termResult->id,
          'student_id' => $student->id,
          'used_at' => now(),
          'academic_session_id' => $termResult->academic_session_id,
          'term' => $termResult->term
        ])
        ->save();
    }
    return $this->successRes($pin->institution, $termResult);
  }

  function canActivate(Pin $pin, TermResult $termResult)
  {
    if (!$pin->term_result_id) {
      return true;
    }
    return $pin->academic_session_id === $termResult->academic_session_id;
  }
}
