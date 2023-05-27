<?php
namespace App\Helpers;

use App\Models\Event;
use App\Models\Institution;
use App\Models\EventSubject;
use App\Models\Exam;
use App\Models\CourseSession;

class EventContentHelper
{
    private $baseDir = APP_DIR.'../public/events-export/';
    
    function downloadEventContent($post) 
    {
        ini_set('max_execution_time', 480);  
        
        $eventId = $post['event_id'];
        $institutionCode = $post['center_code'];
        
        $institution = Institution::whereCode($institutionCode)->first();
        
        if(!$institution) return retF('Institution not found');
        
        $event = Event::where('id', $eventId)->where('institution_id', $institution->id)
        ->first();
        
        if(!$event) return retF('Event not found');
        
        $eventSubjects = EventSubject::where('event_id', $event->id)->get();
        
        if(!$eventSubjects->first()) return retF('Event subject empty');
        
        $exams = Exam::where('event_id', $event->id)->with(['student', 'examSubjects'])->get();
        
        if(!$exams->first()) return retF('Exams empty');
        
        $rootFolder = "event-$event->id";
        $baseFolder = $this->baseDir.$rootFolder;
        
        if(is_dir($baseFolder))
        {
            \App\Core\Helper::deleteDir($baseFolder, false);
        }
        else
        {
            mkdir($baseFolder, 0777, true);
        }
        
        foreach ($eventSubjects as $eventSubject) 
        {
            $session = CourseSession::where('id', $eventSubject->course_session_id)
            ->with(['course', 'questions', 'instructions', 'passages'])->first();
            
            if(!$session) continue;
            
            file_put_contents("$baseFolder/session-{$session->id}.json", json_encode($session->toArray(), JSON_PRETTY_PRINT));
            
            $destinationBaseFolder = "$baseFolder/img";
            
            $this->copySessionImgs($session->course_id, $session->id, $destinationBaseFolder);
        }
        
        foreach ($exams as $exam) 
        {
            file_put_contents("$baseFolder/exam-{$exam->exam_no}.json", json_encode($exam->toArray(), JSON_PRETTY_PRINT));
        }
        
        file_put_contents("$baseFolder/event.json", json_encode($event->toArray(), JSON_PRETTY_PRINT));
        
    	$zipFilename = "$baseFolder/$rootFolder.zip";
    	
    	\App\Core\Helper::zipContent($baseFolder, $zipFilename);
    	
    	$this->downloadContent($zipFilename);
    	
    	// Delete the file afterwards
    	unlink($zipFilename);
    	
    	if(is_dir($baseFolder)) \App\Core\Helper::deleteDir($baseFolder);
    	
    	exit;
    }
    
    private function copySessionImgs($courseId, $course_session_id, $destinationBaseFolder)
    {
        $containingfolder = APP_DIR . "../public/img/content/$courseId/$course_session_id";
        $destinationBaseFolder = "$destinationBaseFolder/$courseId/$course_session_id";
        
        if(!file_exists($containingfolder)) return; //Nothing to copy
            
        if(is_dir($destinationBaseFolder))
        {
            \App\Core\Helper::deleteDir($destinationBaseFolder, false);
        }
        else
        {
            mkdir($destinationBaseFolder, 0777, true);
        }
        
        \App\Core\Helper::copy($containingfolder, $destinationBaseFolder);
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





