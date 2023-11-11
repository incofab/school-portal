<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Actions\StudentMigration;
use App\Http\Controllers\Controller;
use App\Http\Requests\PromoteStudentRequest;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Support\SettingsHandler;
use DB;

class PromoteStudentsController extends Controller
{
  public function create(
    Institution $institution,
    ClassificationGroup $classificationGroup,
    ClassificationGroup $destinationClassificatiinGroup = null
  ) {
    $currentAcademicSessionId = SettingsHandler::makeFromRoute()->getCurrentAcademicSession();
    $sessionResults = SessionResult::query()
      ->select('session_results.*')
      ->join(
        'classifications',
        'session_results.classification_id',
        'classifications.id'
      )
      ->where(
        'classifications.classification_group_id',
        $classificationGroup->id
      )
      ->where('session_results.academic_session_id', $currentAcademicSessionId)
      ->with('student.classification', 'student.user')
      ->get();

    $classes = (
      $destinationClassificatiinGroup?->classifications() ??
      Classification::query()
    )->get();

    abort_if(
      $destinationClassificatiinGroup && $classes->isEmpty(),
      403,
      "There is no class in the selected destination group ({$classificationGroup->title})"
    );

    return inertia('institutions/classifications/promote-students', [
      'classifications' => $classes,
      'classificationGroup' => $classificationGroup,
      'sessionResults' => $sessionResults
    ]);
  }

  public function store(
    PromoteStudentRequest $request,
    Institution $institution,
    ClassificationGroup $classificationGroup
  ) {
    $safeRequest = $request->safe();
    $studentMigration = StudentMigration::make(currentUser());
    $currentAcademicSessionId = SettingsHandler::makeFromRoute()->getCurrentAcademicSession();

    DB::beginTransaction();
    foreach ($safeRequest->promotions as $key => $promotion) {
      $promotion = (object) $promotion;
      $destinationClass = Classification::query()->find(
        $promotion->destination_classification_id
      );
      $sessionResults = SessionResult::query()
        ->select('session_results.*')
        ->join(
          'classifications',
          'session_results.classification_id',
          'classifications.id'
        )
        ->where(
          'classifications.classification_group_id',
          $classificationGroup->id
        )
        ->where(
          'session_results.academic_session_id',
          $currentAcademicSessionId
        )
        ->whereBetween('average', [$promotion->from, $promotion->to])
        ->with('student.classification')
        ->get();

      // info($sessionResults->toArray());
      if ($sessionResults->isEmpty()) {
        // info('Is empty');
        continue;
      }
      // dd('Blocked');
      $batchNo = $studentMigration->generateBatchNo();
      foreach ($sessionResults as $key => $sessionResult) {
        $student = $sessionResult->student;
        if (!$student->classification) {
          continue;
        }
        $studentMigration->migrateStudent(
          $student,
          $student->classification,
          $destinationClass,
          $batchNo
        );
      }
    }
    DB::commit();
  }
}
