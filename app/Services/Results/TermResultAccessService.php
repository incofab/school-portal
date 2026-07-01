<?php

namespace App\Services\Results;

use App\Models\Institution;
use App\Models\Pin;
use App\Models\Student;
use App\Models\TermResult;
use App\Support\Audit\AcademicIntegrityActivityLogger;
use App\Support\SettingsHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TermResultAccessService
{
  public function findPin(string $pin): ?Pin
  {
    return Pin::query()
      ->where('pin', $pin)
      ->with('institution.institutionSettings', 'termResult')
      ->first();
  }

  public function findStudentForPin(Pin $pin, string $studentCode): Student
  {
    $student = Student::query()
      ->select('students.*')
      ->where('students.code', $studentCode)
      ->with('user', 'institutionUser.institution')
      ->firstOrFail();

    $this->validateStudentForPin($pin, $student);

    return $student;
  }

  public function validateStudentForPin(Pin $pin, Student $student): void
  {
    $student->loadMissing('institutionUser.institution');

    if ($student->institutionUser->isSuspended()) {
      throw ValidationException::withMessages([
        'student_code' => 'Access denied. Please contact school authorities'
      ]);
    }

    if (
      $pin->institution->institution_group_id !==
      $student->institutionUser->institution->institution_group_id
    ) {
      throw ValidationException::withMessages([
        'student_code' => 'Student not found'
      ]);
    }

    if ($pin->student_id && $pin->student_id !== $student->id) {
      throw ValidationException::withMessages([
        'pin' => 'This Pin is not for you'
      ]);
    }
  }

  /**
   * @return Collection<int, TermResult>
   */
  public function unactivatedFullTermResults(
    Student $student,
    ?int $termResultId = null
  ): Collection {
    return TermResult::query()
      ->where('institution_id', $student->institutionUser->institution_id)
      ->where('student_id', $student->id)
      ->where('for_mid_term', false)
      ->when($termResultId, fn($q, $value) => $q->where('id', $value))
      ->where('is_activated', false)
      ->with('institution', 'student.user', 'classification', 'academicSession')
      ->get();
  }

  public function latestFullTermResult(Student $student): ?TermResult
  {
    return TermResult::query()
      ->where('institution_id', $student->institutionUser->institution_id)
      ->where('student_id', $student->id)
      ->where('for_mid_term', false)
      ->with('institution', 'student.user', 'classification', 'academicSession')
      ->latest('id')
      ->first();
  }

  /**
   * @return Collection<int, TermResult>
   */
  public function currentTermResults(Student $student): Collection
  {
    $student->loadMissing('institutionUser.institution.institutionSettings');
    $institution = $student->institutionUser?->institution;
    if (!$institution) {
      return new Collection();
    }

    $settings = SettingsHandler::makeFromInstitution($institution);
    $academicSessionId = $settings->getCurrentAcademicSession(null);
    $term = $settings->getCurrentTerm(null);

    if (!$academicSessionId || !$term) {
      return new Collection();
    }

    return $this->resultsForTerm(
      $student,
      (int) $academicSessionId,
      (string) $term
    );
  }

  /**
   * @return Collection<int, TermResult>
   */
  public function resultsForTerm(
    Student $student,
    int $academicSessionId,
    string $term
  ): Collection {
    return TermResult::query()
      ->where('institution_id', $student->institutionUser->institution_id)
      ->where('student_id', $student->id)
      ->where('academic_session_id', $academicSessionId)
      ->where('term', $term)
      ->with('institution', 'student.user', 'classification', 'academicSession')
      ->orderBy('for_mid_term')
      ->get();
  }

  /**
   * @return Collection<int, TermResult>
   */
  public function latestAvailableResults(
    Student $student,
    int $limit = 7
  ): Collection {
    return TermResult::query()
      ->where('institution_id', $student->institutionUser->institution_id)
      ->where('student_id', $student->id)
      ->with('institution', 'student.user', 'classification', 'academicSession')
      ->latest('academic_session_id')
      ->latest('id')
      ->limit($limit)
      ->get();
  }

  /**
   * @param array<int> $termResultIds
   * @return Collection<int, TermResult>
   */
  public function resultsByIds(
    Student $student,
    array $termResultIds
  ): Collection {
    return TermResult::query()
      ->where('institution_id', $student->institutionUser->institution_id)
      ->where('student_id', $student->id)
      ->whereIn('id', $termResultIds)
      ->with('institution', 'student.user', 'classification', 'academicSession')
      ->orderBy('for_mid_term')
      ->get();
  }

  public function checkForPublication(TermResult $termResult): void
  {
    if ($termResult->isPublished()) {
      return;
    }

    throw ValidationException::withMessages([
      'pin' => 'Result has not been published yet, contact school admin'
    ]);
  }

  public function canActivate(Pin $pin, TermResult $termResult): bool
  {
    if (!$pin->term_result_id) {
      return true;
    }

    $settingHandler = SettingsHandler::makeFromInstitution($pin->institution);
    if ($settingHandler->getPinUsageCount() == 1) {
      return false;
    }

    return ($pin->academic_session_id ??
      $pin->termResult->academic_session_id) ===
      $termResult->academic_session_id;
  }

  public function activate(
    TermResult $termResult,
    Pin $pin,
    Student $student
  ): TermResult {
    $this->checkForPublication($termResult);
    $this->validateStudentForPin($pin, $student);

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

    app(AcademicIntegrityActivityLogger::class)->resultPinUsed(
      $pin->institution,
      $pin,
      $termResult,
      $student
    );

    return $termResult->fresh([
      'institution',
      'student.user',
      'classification',
      'academicSession'
    ]);
  }

  public function institutionRequiresActivation(Institution $institution): bool
  {
    return SettingsHandler::makeFromInstitution(
      $institution
    )->resultActivationRequired();
  }
}
