<?php
namespace App\DTO;

use App\Enums\TermType;
use App\Models\TermResult;

class FullTermResult
{
  private ?TermResult $firstTermResult = null;
  private ?TermResult $secondTermResult = null;
  private ?TermResult $thirdTermResult = null;

  function __construct()
  {
  }

  function setTermResult(TermResult $termResult)
  {
    $this->{$termResult->term->value . 'TermResult'} = $termResult;
  }

  function getTermResult(TermType $term): TermResult|null
  {
    return $this->{$term->value . 'TermResult'};
  }

  function getAverage()
  {
    $count = $this->getCount();
    if ($count < 1) {
      return 0;
    }

    return ($this->firstTermResult?->average +
      $this->secondTermResult?->average +
      $this->thirdTermResult?->average) /
      $count;
  }

  function getTotal()
  {
    return $this->firstTermResult?->total_score +
      $this->secondTermResult?->total_score +
      $this->thirdTermResult?->total_score;
  }

  function getTotalAverage()
  {
    return $this->firstTermResult?->average +
      $this->secondTermResult?->average +
      $this->thirdTermResult?->average;
  }

  function getCount()
  {
    $count = 0;
    if ($this->firstTermResult) {
      $count++;
    }
    if ($this->secondTermResult) {
      $count++;
    }
    if ($this->thirdTermResult) {
      $count++;
    }
    return $count;
  }
}
