<?php

namespace App\Enums\Media;

enum MediaStatus: string
{
    case Pending = 'pending';
    case Uploaded = 'uploaded';
    case Failed = 'failed';
}
