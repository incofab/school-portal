<?php
namespace App\DTO;

class ResultSheetColumnIndex
{
  function __construct(
    public string $index,
    public $title,
    public int $width = 10
  ) {
  }
}
