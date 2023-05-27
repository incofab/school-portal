<?php
namespace App\Core;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Summary;

class ExportContent
{
    private $baseDir = APP_DIR.'../public/export/';
    
    function exportCourse(Course $course) 
    {
        ini_set('max_execution_time', 480);  
        
        $courseName = $course['course_code'];
        $courseId = $course['id'];
        
    	$allSessions =  CourseSession::whereCourse_id($courseId)
	           ->with(['passages', 'instructions'])->get();
        
    	$summary =  Summary::where('course_id', '=', $courseId)->get();
    	
    	if (!$allSessions->first())
    	{
    		die("No record found for $courseName, create a new one");
    	}
    	
    	$baseFolder = $this->baseDir.$courseId;
    	
    	if(is_dir($baseFolder))
    	{
    	    \App\Core\Helper::deleteDir($baseFolder, false);
    	}
    	else 
    	{
    	    mkdir($baseFolder, 0777, true);
    	}
    	
    	file_put_contents("$baseFolder/course.json", json_encode($course->toArray(), JSON_PRETTY_PRINT));

    	file_put_contents("$baseFolder/sessions.json", json_encode($allSessions->toArray(), JSON_PRETTY_PRINT));
    	
    	if($summary->first())
    	{
        	file_put_contents("$baseFolder/summary.json", json_encode($summary->toArray(), JSON_PRETTY_PRINT));	    
    	}
    	
    	/** @var CourseSession $session */
    	foreach ($allSessions as $session)
    	{
    	    $question = $session->questions()->get();
    	    
        	file_put_contents("$baseFolder/questions_{$session['session']}_{$session['id']}.json", 
        	       json_encode($question->toArray(), JSON_PRETTY_PRINT));
    	}
    	
    	// The images to copy
    	$imageContentFolder = APP_DIR . '../public/img/content/'."$courseId";
    	
    	// Where to copy the images to
    	$imgBaseFolder = "$baseFolder/img";
    	
    	if(!\App\Core\Helper::is_dir_empty($imageContentFolder))
    	{
    	    mkdir($imgBaseFolder, 0777, true);
    	    
        	\App\Core\Helper::copy($imageContentFolder, $imgBaseFolder);
    	}
    	
    	$currentTime = str_replace([' ', ':'], ['_', '-'], \Carbon\Carbon::now()->toDateTimeString());
    	
    	$zipFilename = "{$this->baseDir}$courseId-$courseName.$currentTime.zip";
    	
    	\App\Core\Helper::zipContent($baseFolder, $zipFilename);
    	
    	$this->downloadContent($zipFilename);
    	
    	// Delete the file afterwards
    	unlink($zipFilename);
    	
    	if(is_dir($baseFolder)) \App\Core\Helper::deleteDir($baseFolder);
    	
    	exit;
    } 
    
    private function downloadContent($fileToDownload) 
    {   
        $file_name = basename($fileToDownload);
        
        header("Content-Type: application/zip");
        
        header("Content-Disposition: attachment; filename=$file_name");
        
        header("Content-Length: " . filesize($fileToDownload));
        
        readfile($fileToDownload);
    }
    
}





