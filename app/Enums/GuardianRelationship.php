<?php

namespace App\Enums;

enum GuardianRelationship: string
{
  case Parent = 'parent';
  case Sibling = 'sibling';
  case Guardian = 'guardian';
  case Nibling = 'nibling';
  case Pibling = 'pibling';
}
