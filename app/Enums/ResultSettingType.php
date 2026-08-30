<?php

namespace App\Enums;

enum ResultSettingType: string
{
    case Template = 'template';
    case PositionDisplayType = 'position-display-type';
    case ExamMode = 'exam-mode';
    case UseSessionResultAsThirdTerm = 'use-session-result-as-third-term';
}
