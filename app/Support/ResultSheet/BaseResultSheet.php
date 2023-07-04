<?php

namespace App\Support\UITableFilters;

use App\Models\ClassResultInfo;
use App\Models\TermResult;
use App\Support\SettingsHandler;

class BaseResultSheet
{
  protected string $template;
  protected array $courseResultInfoData = [];

  public function __construct(
    private TermResult $termResult,
    private ClassResultInfo $classResultInfo,
    protected array $courseResults,
    protected array $courseResultInfo
  ) {
    $this->template = SettingsHandler::makeFromRoute()->getResultTemplate();

    foreach ($courseResultInfo as $key => $value) {
      $courseResultInfoData[$value->course_id] = $value;
    }
  }

  function setTemplate($template)
  {
    $this->template = $template;
  }

  private function getViewData()
  {
    return [
      'institution' => $this->termResult->institution,
      'courseResults' => $this->courseResults,
      'student' => $this->termResult->student->load('user'),
      'classification' => $this->termResult->classification,
      'academicSession' => $this->termResult->academicSession,
      'term' => $this->termResult->term,
      'termResult' => $this->termResult,
      'classResultInfo' => $this->classResultInfo,
      'courseResultInfoData' => $this->courseResultInfoData,
      'resultDetails' => $this->getResultDetails()
    ];
  }

  private function getResultDetails()
  {
    return [
      [
        'label' => "Student's Total Score",
        'value' => $this->termResult->total_score
      ],
      [
        'label' => 'Maximum Total Score',
        'value' => $this->classResultInfo->max_obtainable_score
      ],
      [
        'label' => "Student's Average Score",
        'value' => $this->termResult->average
      ],
      [
        'label' => 'Class Average Score',
        'value' => $this->classResultInfo->average
      ]
    ];
  }

  function display()
  {
    return view("student-result-sheet-{$this->template}", $this->getViewData());
  }
}
