<?php
namespace App\Helpers;

use App\Models\ExamContent;
use App\Models\Course;
use App\Models\User;
use App\Models\Exam;
use App\Models\ExamSubject;
use Illuminate\Support\Facades\DB;

class CoursesHelper
{
    private $formatExam;
    private $examHandler;
    
    public function __construct(
        FormatExam $formatExam,
        ExamHandler $examHandler
    ){
        $this->formatExam = $formatExam;
        $this->examHandler = $examHandler;
    }
    
    
}

