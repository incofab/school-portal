<?php

namespace App\Enums;

enum LibrarySourceType: string
{
    case Upload = 'upload';
    case External = 'external';
}
