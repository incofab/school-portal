<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use DB;
use Inertia\Inertia;

class TransferCourseResultInfoController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function create(
    Institution $institution,
    CourseResultInfo $courseResultInfo,
    Request $request
  ) {
    abort_unless($courseResultInfo->institution_id === $institution->id, 404);
    $this->authorizeTransfer($courseResultInfo);

    $targetTerm = $request->get('targetTerm');
    $targetForMidTerm = $request->boolean('targetForMidTerm', false);
    $targetTerm = $targetTerm ?? ($courseResultInfo->term?->value ?? null);

    $sourceTerm = $courseResultInfo->term?->value ?? $courseResultInfo->term;
    $sourceForMidTerm = (bool) $courseResultInfo->for_mid_term;
    $sourceAssessments = Assessment::getAssessments(
      $sourceTerm,
      $sourceForMidTerm,
      $courseResultInfo->classification_id
    );

    $assessments = Assessment::query()
      ->where('institution_id', $institution->id)
      ->with('classifications')
      ->get();

    return Inertia::render('institutions/courses/transfer-course-result-info', [
      'courseResultInfo' => $courseResultInfo->load(
        'course',
        'classification',
        'academicSession'
      ),
      'sourceAssessments' => $sourceAssessments,
      'assessments' => $assessments,
      'targetTerm' => $targetTerm,
      'targetForMidTerm' => $targetForMidTerm
    ]);
  }

  public function store(
    Institution $institution,
    CourseResultInfo $courseResultInfo,
    Request $request
  ) {
    $courseTeacher = $this->authorizeTransfer($courseResultInfo);

    $data = $request->validate([
      'term' => ['required', new Enum(TermType::class)],
      'for_mid_term' => ['required', 'boolean'],
      'assessment_map' => ['required', 'array'],
      'assessment_map.*' => ['nullable', 'array'],
      'assessment_map.*.*' => ['nullable']
    ]);

    $fromTerm = $courseResultInfo->term?->value ?? $courseResultInfo->term;
    $fromForMidTerm = (bool) $courseResultInfo->for_mid_term;
    $toTerm = $data['term'];
    $toForMidTerm = (bool) $data['for_mid_term'];

    if ($fromTerm === $toTerm && $fromForMidTerm === $toForMidTerm) {
      throw ValidationException::withMessages([
        'term' =>
          'Select a different term or mid-term option to transfer results.'
      ]);
    }

    $sourceAssessments = Assessment::getAssessments(
      $fromTerm,
      $fromForMidTerm,
      $courseResultInfo->classification_id
    );
    $targetAssessments = Assessment::getAssessments(
      $toTerm,
      $toForMidTerm,
      $courseResultInfo->classification_id
    );

    $sourceIds = $sourceAssessments
      ->pluck('id')
      ->values()
      ->toArray();
    $targetIds = $targetAssessments
      ->pluck('id')
      ->values()
      ->toArray();
    $targetIds[] = 'exam';
    $assessmentMap = $data['assessment_map'];

    $targetKeyList = array_map('strval', $targetIds);
    $missingTargetKeys = array_diff($targetKeyList, array_keys($assessmentMap));
    if (!empty($missingTargetKeys)) {
      throw ValidationException::withMessages([
        'assessment_map' =>
          'Provide mapping entries for all target assessments.'
      ]);
    }

    $sourceOptions = [...array_map('strval', $sourceIds), 'exam'];
    $invalidSources = collect($assessmentMap)
      ->flatMap(fn($values) => (array) $values)
      ->filter(function ($value) use ($sourceOptions) {
        if ($value === null || $value === '') {
          return false;
        }
        return !in_array((string) $value, $sourceOptions, true);
      })
      ->values()
      ->toArray();
    if (!empty($invalidSources)) {
      throw ValidationException::withMessages([
        'assessment_map' =>
          'One or more mapped values are invalid for the selected term.'
      ]);
    }

    $binding = [
      'institution_id' => $courseResultInfo->institution_id,
      'course_id' => $courseResultInfo->course_id,
      'classification_id' => $courseResultInfo->classification_id,
      'academic_session_id' => $courseResultInfo->academic_session_id,
      'term' => $fromTerm,
      'for_mid_term' => $fromForMidTerm
    ];

    $courseResults = CourseResult::query()
      ->where($binding)
      ->get();
    if ($courseResults->isEmpty()) {
      throw ValidationException::withMessages([
        'assessment_map' => 'No results found to transfer.'
      ]);
    }

    $courseTeacher =
      $courseTeacher ??
      CourseTeacher::query()
        ->where('course_id', $courseResultInfo->course_id)
        ->where('classification_id', $courseResultInfo->classification_id)
        ->where('user_id', $courseResults->first()->teacher_user_id)
        ->first();
    if (!$courseTeacher) {
      throw ValidationException::withMessages([
        'course_teacher_id' =>
          'No course teacher found for one or more results.'
      ]);
    }
    DB::transaction(function () use (
      $courseResults,
      $assessmentMap,
      $toTerm,
      $toForMidTerm,
      $courseTeacher,
      $sourceAssessments,
      $targetAssessments
    ) {
      $sourceRawById = $sourceAssessments->pluck('raw_title', 'id')->toArray();
      $targetRawById = $targetAssessments->pluck('raw_title', 'id')->toArray();

      foreach ($courseResults as $courseResult) {
        $mappedAssessments = array_fill_keys(
          [...array_values($targetRawById), 'exam'],
          0
        );
        $sourceValues = (array) ($courseResult->assessment_values ?? []);
        $sourceValues['exam'] = $courseResult->exam ?? 0;

        foreach ($assessmentMap as $targetId => $sourceIds) {
          $targetIdValue = (string) $targetId;
          $targetKey =
            $targetIdValue === 'exam'
              ? 'exam'
              : $targetRawById[(int) $targetIdValue] ?? null;
          if (!$targetKey) {
            continue;
          }
          $ids = (array) $sourceIds;
          foreach ($ids as $sourceId) {
            if ($sourceId === null || $sourceId === '') {
              continue;
            }
            $sourceIdValue = (string) $sourceId;
            $sourceKey =
              $sourceIdValue === 'exam'
                ? 'exam'
                : $sourceRawById[(int) $sourceIdValue] ?? null;
            if (!$sourceKey) {
              continue;
            }
            $score = $sourceValues[$sourceKey] ?? 0;
            $mappedAssessments[$targetKey] =
              ($mappedAssessments[$targetKey] ?? 0) + $score;
          }
        }

        RecordCourseResult::run(
          [
            'institution_id' => $courseResult->institution_id,
            'student_id' => $courseResult->student_id,
            'academic_session_id' => $courseResult->academic_session_id,
            'term' => $toTerm,
            'for_mid_term' => $toForMidTerm,
            'exam' => $mappedAssessments['exam'] ?? 0,
            'remark' => $courseResult->remark,
            'ass' => collect($mappedAssessments)
              ->except('exam')
              ->toArray()
          ],
          $courseTeacher,
          false
        );
      }
    });

    $classification = $courseResultInfo->classification()->first();
    if ($classification) {
      EvaluateCourseResultForClass::run(
        $classification,
        $courseResultInfo->course_id,
        $courseResultInfo->academic_session_id,
        $toTerm,
        $toForMidTerm
      );
    }

    return $this->ok();
  }

  private function authorizeTransfer(
    CourseResultInfo $courseResultInfo
  ): CourseTeacher|null {
    $currentInstitutionUser = currentInstitutionUser();
    if ($currentInstitutionUser->isAdmin()) {
      return null;
    }
    $courseTeacher = CourseTeacher::query()
      ->where('course_id', $courseResultInfo->course_id)
      ->where('classification_id', $courseResultInfo->classification_id)
      ->where('user_id', $currentInstitutionUser->user_id)
      ->first();

    abort_unless(
      $courseTeacher,
      403,
      'You can only transfer results for your course'
    );
    return $courseTeacher;
  }
}
