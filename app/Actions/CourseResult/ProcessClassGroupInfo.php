<?php

namespace App\Actions\CourseResult;

use App\Actions\ResultUtil;
use App\Models\ClassGroupResultInfo;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Institution;
use App\Models\TermResult;

class ProcessClassGroupInfo
{
  // private Institution $institution;
  public function __construct(
    private Institution $institution,
    private Classification $classification,
    private int $academicSessionId,
    private string $term,
    private bool $forMidTerm = false
  ) {
  }

  static function makeFromClassResultInfo(
    ClassResultInfo $classResultInfo,
    ?Institution $institution = null
  ): self {
    return new self(
      $institution ?? $classResultInfo->institution,
      $classResultInfo->classification,
      $classResultInfo->academic_session_id,
      $classResultInfo->term->value,
      $classResultInfo->for_mid_term
    );
  }

  function getBindingData()
  {
    return [
      'institution_id' => $this->institution->id,
      // 'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSessionId,
      'term' => $this->term,
      'for_mid_term' => $this->forMidTerm
    ];
  }

  function run()
  {
    $classGroup = $this->classification->classificationGroup;
    $classifications = $classGroup->classifications()->get();

    $classResultInfos = \App\Models\ClassResultInfo::query()
      ->where($this->getBindingData())
      ->whereIn('classification_id', $classifications->pluck('id'))
      ->groupBy('classification_id')
      ->get();

    if ($classResultInfos->count() != $classifications->count()) {
      return;
    }

    $termResultsAverage = \App\Models\TermResult::query()
      ->where($this->getBindingData())
      ->whereIn('classification_id', $classifications->pluck('id'))
      ->get(['id', 'average'])
      ->pluck('average', 'id')
      ->toArray();
    // dd($termResultsAverage);
    $assignedPositions = ResultUtil::assignPositions($termResultsAverage);

    $rows = [];
    foreach ($assignedPositions as $assignedPosition) {
      $rows[] = [
        'id' => (int) $assignedPosition->getId(),
        'class_group_position' => (int) $assignedPosition->getPosition()
      ];
    }

    $numOfStudents = $classResultInfos->sum('num_of_students');
    $totalScore = $classResultInfos->sum('total_score');

    ClassGroupResultInfo::query()->updateOrCreate(
      [
        'institution_id' => $this->institution->id,
        'term' => $this->term,
        'for_mid_term' => $this->forMidTerm,
        'academic_session_id' => $this->academicSessionId,
        'classification_group_id' =>
          $this->classification->classification_group_id
      ],
      [
        'num_of_students' => $numOfStudents,
        'total_score' => $totalScore,
        'max_obtainable_score' => $classResultInfos->sum(
          'max_obtainable_score'
        ),
        'average' => round($totalScore / $numOfStudents, 2),
        'min_score' => $classResultInfos->min('min_score'),
        'max_score' => $classResultInfos->max('max_score')
      ]
    );

    collect($rows)
      ->chunk(1000)
      ->each(
        fn($chunk) => TermResult::upsert(
          $chunk->toArray(),
          ['id'],
          ['class_group_position']
        )
      );
  }
}
