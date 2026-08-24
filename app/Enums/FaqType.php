<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum FaqType: string
{
  use EnumToArray;

  case Faq = 'faq';
  case KnowledgeBase = 'knowledge_base';
}
