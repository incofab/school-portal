<?php

namespace App\Enums;

enum ResultCommentTemplateType: string
{
  case MidTermResult = 'mid-term-result';
  case FullTermResult = 'term-result';
  case SessionResult = 'session-result';
}
