<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Pin;
use App\Models\Student;
use App\Models\TermResult;
use App\Services\Results\TermResultAccessService;
use App\Support\SettingsHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use URL;

class TermResultActivationController extends Controller
{
  public function __construct(private TermResultAccessService $resultAccess)
  {
  }

  public function create()
  {
    if (!$this->isActivationPinNeeded()) {
      return redirect()->route('student-login');
    }

    return inertia('auth/activate-student-term-result', [
      'institutionGroup' => getInstitutionGroupFromDomain()
    ]);
  }

  private function isActivationPinNeeded(): bool
  {
    $institutionGroup = getInstitutionGroupFromDomain();
    if (!$institutionGroup || $institutionGroup->institutions->isEmpty()) {
      return true;
    }

    $institutionGroup->load('institutions.institutionSettings');
    foreach ($institutionGroup->institutions as $institution) {
      $settingHandler = SettingsHandler::makeFromInstitution($institution);
      if ($settingHandler->resultActivationRequired()) {
        return true;
      }
    }

    return false;
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'student_code' => ['required', 'exists:students,code'],
      'pin' => ['required'],
      'term_result_id' => ['nullable']
    ]);

    $pin = $this->resultAccess->findPin($data['pin']);

    if (!$pin) {
      throw ValidationException::withMessages(['pin' => 'Invalid pin']);
    }
    $institution = $pin->institution;

    $student = $this->resultAccess->findStudentForPin(
      $pin,
      $data['student_code']
    );

    $termResults = $this->resultAccess->unactivatedFullTermResults(
      $student,
      $request->term_result_id ? (int) $request->term_result_id : null
    );

    $count = $termResults->count();
    if ($count === 0) {
      $latestTermResult = $this->resultAccess->latestFullTermResult($student);
      if ($latestTermResult) {
        $this->resultAccess->checkForPublication($latestTermResult);

        return $this->successRes(
          $latestTermResult->institution,
          $latestTermResult
        );
      }

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
      'term_results' => $this->mergedTermResults(
        $institution,
        $student,
        $termResults
      )
    ]);
  }

  private function mergedTermResults(
    Institution $institution,
    Student $student,
    Collection $termResults
  ) {
    $settingHandler = SettingsHandler::makeFromInstitution($institution);
    $academicSessionId = $settingHandler->getCurrentAcademicSession();
    $currentTerm = $settingHandler->getCurrentTerm();

    if (!$academicSessionId || !$currentTerm) {
      return $termResults;
    }
    $currentTermResult = TermResult::where(
      'academic_session_id',
      $academicSessionId
    )
      ->where('term', $currentTerm)
      ->where('institution_id', $institution->id)
      ->where('student_id', $student->id)
      ->where('for_mid_term', false)
      ->first();
    $last5Results = TermResult::where('institution_id', $institution->id)
      ->where('student_id', $student->id)
      ->where('for_mid_term', false)
      ->latest('id')
      ->take(5)
      ->get();

    if ($currentTermResult && $termResults->doesntContain($currentTermResult)) {
      return $termResults->push($currentTermResult);
    }
    foreach ($last5Results as $result) {
      if ($termResults->doesntContain($result)) {
        $termResults->push($result);
      }
    }

    return $termResults->each(
      fn($t) => $t->setAttribute('signed_url', $t->signedUrl())
    );
  }

  private function successRes(Institution $institution, TermResult $termResult)
  {
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
    $termResult = $this->resultAccess->activate($termResult, $pin, $student);

    return $this->successRes($pin->institution, $termResult);
  }
}
