<?php
namespace App\DTO;

class SheetColumnIndex
{
  function __construct(
    public string $index,
    public $title,
    public int $width = 10
  ) {
  }
}
