<?php

namespace App\Enums;

enum NoteStatusType: string
{
  case Draft = 'draft';
  case Published = 'published';
}