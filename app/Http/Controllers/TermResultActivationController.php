<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Pin;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Auth;
use File;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TermResultActivationController extends Controller
{
  public function create()
  {
    return inertia('activate-student-term-result');
  }

  public function showPdfResult(Student $student)
  {
    $fileFromPublicPath = "wisegate/{$student->code}.pdf";

    if (request('download')) {
      return response()->download(public_path($fileFromPublicPath));
    }

    return inertia('show-pdf-result', [
      'path' => asset($fileFromPublicPath),
      'student' => $student
    ]);
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'student_code' => ['required'],
      'pin' => ['required'],
      'term_result_id' => ['nullable']
    ]);

    $pin = Pin::query()
      ->where('pin', $data['pin'])
      ->with('institution')
      ->first();

    if (!$pin) {
      return throw ValidationException::withMessages(['pin' => 'Invalid pin']);
    }

    if ($pin->isUsed()) {
      if ($pin->term_result_id) {
        return $this->handleUsedPin($pin);
      }
      return throw ValidationException::withMessages([
        'pin' => 'Pin has already been used'
      ]);
    }

    $student = Student::query()
      ->select('students.*')
      ->forInstitution($pin->institution_id)
      ->where('students.code', $data['student_code'])
      ->with('user')
      ->firstOrFail();

    if (!$student) {
      return throw ValidationException::withMessages([
        'student_code' => 'Invalid student ID'
      ]);
    }

    if ($this->hasPdfResult($student, $pin)) {
      return response()->json([
        'redirect_url' => route('show-pdf-result', [$student])
      ]);
    }

    $termResultQuery = TermResult::query()
      ->where('institution_id', $pin->institution_id)
      ->where('student_id', $student->id)
      ->activated(false);

    if ($request->term_result_id) {
      $termResult = (clone $termResultQuery)
        ->where('term_results.id', $request->term_result_id)
        ->first();
      if ($termResult) {
        $this->activateResult($termResult, $pin, $student->user);
        return $this->successRes($pin->institution, $termResult);
      }
    }

    $termResults = (clone $termResultQuery)
      ->with('classification', 'academicSession')
      ->get();

    $count = $termResults->count();
    if ($count === 0) {
      return response()->json([
        'redirect_url' => route('student-login'),
        'message' =>
          'No unactivated result found, Login to see already activated results'
      ]);
    }

    if ($count === 1) {
      $this->activateResult($termResults->first(), $pin, $student->user);
      return $this->successRes($pin->institution, $termResults->first());
    }

    return response()->json([
      'has_multiple_results' => true,
      'term_results' => $termResults
    ]);
  }

  private function successRes(Institution $institution, TermResult $termResult)
  {
    $route = route('institutions.students.result-sheet', [
      $institution->uuid,
      $termResult->student_id,
      $termResult->classification_id,
      $termResult->academic_session_id,
      $termResult->term,
      $termResult->for_mid_term ? 1 : 0
    ]);

    return response()->json([
      'redirect_url' => $route
      // route('institutions.students.term-results.index', [237764964824,20238750
      //   $institution->uuid
      // ])
    ]);
  }

  private function activateResult(TermResult $termResult, Pin $pin, User $user)
  {
    $termResult->fill(['is_activated' => true])->save();
    $pin
      ->fill([
        'term_result_id' => $termResult->id,
        'used_at' => now()
      ])
      ->save();
    if (!Auth::check()) {
      Auth::login($user);
    }
  }

  private function handleUsedPin(Pin $usedPin)
  {
    $termResult = TermResult::query()
      ->where('id', $usedPin->term_result_id)
      ->with('student.user')
      ->first();
    abort_unless($termResult, 403, 'Activated result not found');
    if (!Auth::check()) {
      Auth::login($termResult->student->user);
    }
    return $this->successRes($termResult->institution, $termResult);
  }

  private function hasPdfResult(Student $student, Pin $pin)
  {
    $fileFromPublicPath = "wisegate/{$student->code}.pdf";
    if (!File::exists(public_path($fileFromPublicPath))) {
      return false;
    }
    //$pin->fill(['used_at' => now()])->save();
    return true;
  }
}
