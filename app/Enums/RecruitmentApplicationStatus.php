<?php

namespace App\Enums;

enum RecruitmentApplicationStatus: string
{
    case Pending = 'pending';
    case Shortlisted = 'shortlisted';
    case Hired = 'hired';
    case Declined = 'declined';
}
