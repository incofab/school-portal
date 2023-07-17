<?php

namespace App\Enums;

enum LearningEvaluationDomainType: string
{
  case Text = 'text';
  case Number = 'number';
  case YesOrNo = 'yes-or-no';
}
