<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Actions\Result\GetViewSessionResultSheetData;
use App\Actions\Result\GetViewResultSheetData;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermResult;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;
use URL;

class ViewResultSheetController extends Controller
{
  private function validateStudent(
    Student $student,
    Classification $classification
  ) {
    $institutionUser = currentInstitutionUser();
    if ($institutionUser->isAdmin()) {
      return;
    }
    if ($classification->form_teacher_id === $institutionUser->user_id) {
      return;
    }
    if ($institutionUser->user_id == $student->user_id) {
      return;
    }

    if (
      GuardianStudent::isGuardianOfStudent(
        $institutionUser->user_id,
        $student->id
      )
    ) {
      return;
    }

    abort(403, 'You are not authorized to view this result');
  }

  public function viewResult(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    ?bool $forMidTerm = false
  ) {
    $this->validateStudent($student, $classification);
    /** @var TermResult|null $termResult */
    $termResult = $this->findTermResult(
      $institution,
      $student,
      $classification,
      $academicSession,
      $term,
      $forMidTerm
    );
    abort_unless($termResult, 404, 'Result not found');
    abort_unless($termResult->isPublished(), 403, 'Result not published');

    if (!currentInstitutionUser()->isAdmin()) {
      abort_unless(
        $termResult->isActivated(),
        403,
        'This result is not activated'
      );
    }

    if ($this->shouldUseSessionResult($institution, $term, $forMidTerm)) {
      $sessionResult = $this->thirdTermSessionResult(
        $institution,
        $student,
        $classification,
        $academicSession,
        !currentInstitutionUser()->isAdmin()
      );

      return $this->displaySessionResult(
        GetViewSessionResultSheetData::run(
          $sessionResult,
          onlyActivatedTermResults: !currentInstitutionUser()->isAdmin()
        )
      );
    }

    $viewData = GetViewResultSheetData::run(
      $institution,
      $student,
      $classification,
      $academicSession,
      $term,
      $forMidTerm
    );
    $viewData['signed_url'] = $termResult?->signedUrl();

    return $this->display($viewData);
  }

  public function viewResultSigned(
    Request $request,
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    ?bool $forMidTerm = false
  ) {
    // abort_unless($request->hasValidSignature(), 403, 'Access denied');

    $termResult = $this->findTermResult(
      $institution,
      $student,
      $classification,
      $academicSession,
      $term,
      $forMidTerm
    );
    /** @var TermResult|null $termResult */
    abort_unless($termResult, 404, 'Result not found');

    if ($this->shouldUseSessionResult($institution, $term, $forMidTerm)) {
      $sessionResult = $this->thirdTermSessionResult(
        $institution,
        $student,
        $classification,
        $academicSession
      );

      return $this->displaySessionResult(
        GetViewSessionResultSheetData::run(
          $sessionResult,
          onlyActivatedTermResults: true
        )
      );
    }

    $viewData = GetViewResultSheetData::run(
      $institution,
      $student,
      $classification,
      $academicSession,
      $term,
      $forMidTerm
    );
    $viewData['signed_url'] = $termResult->signedUrl();
    // abort_unless($termResult->isActivated(), 403, 'Result not activated');
    return $this->display($viewData);
  }

  private function display(array $viewData)
  {
    $classification = $viewData['classification'];
    $setting = SettingsHandler::makeFromRoute();
    $template =
      $classification->classDivisions()->first()?->result_template ??
      $setting->getResultTemplate();
    return inertia("institutions/result-sheets/{$template}", $viewData);
  }

  private function displaySessionResult(array $viewData)
  {
    return inertia(
      'institutions/session-result-sheets/session-result-template-1',
      $viewData
    );
  }

  private function shouldUseSessionResult(
    Institution $institution,
    string $term,
    bool $forMidTerm
  ): bool {
    return SettingsHandler::makeFromInstitution(
      $institution
    )->usesSessionResultAsThirdTerm() &&
      $term === TermType::Third->value &&
      !$forMidTerm;
  }

  private function findTermResult(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm
  ): ?TermResult {
    return TermResult::query()
      ->where('institution_id', $institution->id)
      ->where('student_id', $student->id)
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm)
      ->first();
  }

  private function thirdTermSessionResult(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    bool $requireActivation = true
  ): SessionResult {
    $sessionResult = SessionResult::query()
      ->where('institution_id', $institution->id)
      ->where('student_id', $student->id)
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->first();

    abort_unless($sessionResult, 404, 'Session result not found');

    if ($requireActivation) {
      abort_unless(
        TermResult::query()
          ->where('institution_id', $institution->id)
          ->where('student_id', $student->id)
          ->where('classification_id', $classification->id)
          ->where('academic_session_id', $academicSession->id)
          ->where('for_mid_term', false)
          ->whereIn(
            'term',
            array_map(
              fn(TermType $term) => $term->value,
              TermType::cases()
            )
          )
          ->where('is_activated', true)
          ->select('term')
          ->distinct()
          ->count() === count(TermType::cases()),
        403,
        'Activate the first, second, and third-term results before viewing the session result'
      );
    }

    return $sessionResult;
  }

  function pdfBridge(Request $request)
  {
    $data = $request->validate([
      'url' => ['required', 'string'],
      'filename' => ['required', 'string']
    ]);
    // dd($data);
    $filename = $request->filename;
    $filePath = storage_path(self::STORAGE_PATH . $request->filename);
    if (!file_exists($filePath)) {
      $downloadUrl = config('services.pdf.url') . '?' . http_build_query($data);

      $this->downloadFile($downloadUrl, $filename);
    }
    return $this->responseDownload($filePath, $filename);

    // $res = Http::post(config('services.pdf.url'), $data);
    // abort_unless(
    //   $res->ok(),
    //   401,
    //   'Initial PDF error encountered, Alternative means will be used'
    // );
    // return $this->ok(['filename' => $request->filename]);
  }

  const STORAGE_PATH = 'app/public/result-pdf/';
  /** @deprecated */
  function pdfBridgeDownload(Request $request)
  {
    $data = $request->validate([
      'filename' => ['required', 'string']
    ]);

    $filename = $request->filename;
    $filePath = storage_path(self::STORAGE_PATH . $filename);

    if (!file_exists($filePath)) {
      $downloadUrl =
        config('services.pdf.url') . '/download?' . http_build_query($data);
      $this->downloadFile($downloadUrl, $filename);
    }
    return $this->responseDownload($filePath, $filename);
  }

  private function responseDownload(string $filePath, string $filename)
  {
    $headers = [
      'Content-Type' => 'application/pdf',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"'
    ];
    return response()->download($filePath, $filename, $headers);
  }

  private function downloadFile(string $url, string $filename)
  {
    try {
      $savePath = storage_path(self::STORAGE_PATH); // Adjust the path as needed
      if (!file_exists($savePath)) {
        mkdir($savePath, 0777, true);
      }
      $fileContent = file_get_contents($url);
      file_put_contents("$savePath$filename", $fileContent);
    } catch (\Throwable $th) {
      info('Error downloading result sheet pdf: ' . $th->getMessage());
      abort(401, 'Error downloading result sheet');
    }
  }
}
