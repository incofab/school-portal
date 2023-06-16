<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pin;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TermResultActivationController extends Controller
{
  public function create()
  {
    return inertia('activate-student-term-result');
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
      ->used(false)
      ->with('institution')
      ->first();

    if (!$pin) {
      return throw ValidationException::withMessages(['pin' => 'Invalid pin']);
    }

    $student = Student::query()
      ->forInstitution($pin->institution_id)
      ->where('students.code', $data['student_code'])
      ->with('user')
      ->firstOrFail();

    if (!$student) {
      return throw ValidationException::withMessages([
        'student_code' => 'Invalid student ID'
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
        return $this->successRes($pin->institution);
      }
    }

    $termResults = (clone $termResultQuery)
      ->with('classification', 'academicSession')
      ->get();

    $count = $termResults->count();
    if ($count === 0) {
      abort(Response::HTTP_BAD_REQUEST, 'No unactivated result found');
      return;
    }

    if ($count === 1) {
      $this->activateResult($termResults->first(), $pin, $student->user);
      return $this->successRes($pin->institution);
    }

    return response()->json([
      'has_multiple_results' => true,
      'term_results' => $termResults
    ]);
  }

  private function successRes($institution)
  {
    return response()->json([
      'redirect_url' => route('institutions.students.term-results.index', [
        $institution->uuid
      ])
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
}
