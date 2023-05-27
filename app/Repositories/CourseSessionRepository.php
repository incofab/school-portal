<?php
namespace App\Repositories;

use App\Models\CourseSession;

class CourseSessionRepository{
    
    function show($institutionId, $courseSessionId)
    {
        return CourseSession::select('course_sessions.*')->whereId($courseSessionId)
        ->join('courses', 'courses.institution_id', $institutionId)
        ->with('course')
        ->first();
    }
    
    
    
}