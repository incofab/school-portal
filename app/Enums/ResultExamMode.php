<?php

namespace App\Enums;

enum ResultExamMode: string
{
    case None = 'none';
    case MidTerm = 'mid-term';
    case FullTerm = 'full-term';
    case Both = 'both';
}
