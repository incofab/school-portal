<?php
namespace App\DTO;

class AssignedPosition
{
  function __construct(
    private string|int $id,
    private int|float $score,
    private int $position
  ) {
  }

  function getId()
  {
    return $this->id;
  }

  function getScore()
  {
    return $this->score;
  }

  function getPosition()
  {
    return $this->position;
  }
}
