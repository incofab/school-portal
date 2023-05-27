<?php
namespace App\Core;

use App\Models\BaseModel;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Instruction;
use App\Models\Question;
use App\Models\Summary;
use App\Models\Passage;
use Illuminate\Support\Facades\DB;

class CourseInstaller
{
    const INSTALL_FROM_HTML = false;
    
    public $coursesFolder = APP_DIR.'../public/courses/'.(self::INSTALL_FROM_HTML ? 'html/' : 'json/');
    public static $imagesBaseFolder = APP_DIR."../public/img/content/";
    
    const IS_INSTALLED_SUFFIX = '.installed.zip';
    
    private $extractionFolder = APP_DIR."../public/files/content/extracted/installed-courses/";
    
    private $coursesModel;
    private $sessionModel;
    private $passagesModel;
    private $instructionsModel;
    private $questionsModel;
    private $summaryModel;
    
    function __construct()
    {
        if(!file_exists($this->coursesFolder)){
            mkdir($this->coursesFolder, 0777, true);
        }
    }
    
    function isCourseInstalled($courseId) 
    {
        return file_exists($this->coursesFolder.$courseId.self::IS_INSTALLED_SUFFIX);
    }
    
    function canInstallCourse($courseId) 
    {
        return file_exists($this->coursesFolder.$courseId.'.zip');
    }
    
    function installCourse($courseId) 
    {
        DB::beginTransaction();
        
        $ret = $this->startInstallation($courseId);
//         dlog($ret);
        if($ret[SUCCESSFUL])
        {
            DB::commit();
            
            rename($this->coursesFolder.$courseId.'.zip', $this->coursesFolder.$courseId.self::IS_INSTALLED_SUFFIX);
            
            // Delete the folder with the extracted files
            $extractedFilesDir = $this->extractionFolder.$courseId;
            
            if(is_dir($extractedFilesDir)) \App\Core\Helper::deleteDir($extractedFilesDir);
            
        }else{
            DB::rollBack();
        }
        
        return $ret;
    }
    
    private function startInstallation($courseId)
    {
        $contentPath = $this->coursesFolder.$courseId.'.zip';
        
//         if(file_exists($this->coursesFolder.$courseId.self::IS_INSTALLED_SUFFIX)) 
//         {
//             return [SUCCESSFUL => false, MESSAGE => 'Content already installed'];
//         }
        
        if(!file_exists($contentPath)) return [SUCCESSFUL => false, MESSAGE => 'Error: File not found'];
            
        ini_set('max_execution_time', 1440);  
        
        $ret = $this->extractAndFormatJSONContent($contentPath, $courseId);

//         dDie($ret);    
        return $ret;
    }
	
	
	
	
	

	
	private function extractAndFormatJSONContent($contentPath, $courseId)
	{
	    $course = Course::whereId($courseId)->first();
	    
	    if(empty($course)) return retF('Error: Course not found');
	    
	    $contentDir = $this->extractionFolder.$courseId;

	    $ret = \App\Core\Helper::unzip($contentPath, $contentDir);
	    
	    if(!$ret[SUCCESSFUL]) return $ret;
	    
	    $courseFilename = "$contentDir/course.json";
	    $sessionsFilename = "$contentDir/sessions.json";
	    $summaryFilename = "$contentDir/summary.json";
	    $summaryData = null;
	    
	    if(!file_exists($courseFilename)) return retF('Error: Course file not found');	        
	    
	    if(!file_exists($sessionsFilename)) return retF('Error: Sessions file not found');	        
	    
	    if(file_exists($summaryFilename))
	    {
    	    $summaryData = json_decode(file_get_contents($summaryFilename), true);
    	    
    	    $summaryVal = $this->validateSummary($summaryData, $courseId);
    
    	    if(!$summaryVal[SUCCESSFUL]) return $summaryVal;
	    }
	    	    
	    $allSessionData = json_decode(file_get_contents($sessionsFilename), true);
	    
	    $sessionVal = $this->validateSessions($allSessionData, $contentDir, $courseId);

	    if(!$sessionVal[SUCCESSFUL]) return $sessionVal;
	    
	    if(empty($allSessionData)) return retF('Error: Sessions file content empty');
	    
// 	    $sessionIDMappingOldToNew = [];
	    $allUpdatedSessions = [];
	    $allSessionDataCopy = [];
	    // Now start saving records to database
        foreach ($allSessionData as $sessionData) 
        {
            $updatedSessionData = CourseSession::whereCourse_id($courseId)
            ->whereSession($sessionData['session'])->first();
            
            //Check if session already exists
            if($updatedSessionData) continue;
            
            // $updatedSessionData updated with the new DB record
    	    $updatedSessionData = CourseSession::create([
                'course_id' => $courseId,
                'session' => $sessionData['session'],
                'category' => $sessionData['category'],
                'general_instructions' => $sessionData['general_instructions'],
    	    ]);
            
//             $sessionIDMappingOldToNew[$sessionData['id']] = $updatedSessionData['id'];
            $allUpdatedSessions[] = $updatedSessionData;
            $allSessionDataCopy[] = $sessionData;
            
    	    $passages = $sessionData['passages'];
    	    
    	    foreach ($passages as $passage) 
    	    {
    	        Passage::create([
    	            'course_session_id' => $updatedSessionData['id'],
    	            'passage' => $passage['passage'],
    	            'from' => $passage['from'],
    	            'to' => $passage['to'],
    	        ]);
    	    }
    	    
    	    $instructions = $sessionData['instructions'];
        
    	    foreach ($instructions as $instruction) 
    	    {
    	        Instruction::create([
    	            'course_session_id' => $updatedSessionData['id'],
    	            'instruction' => $instruction['instruction'],
    	            'from' => $instruction['from'],
    	            'to' => $instruction['to'],
    	        ]);
    	    }
    	    
    	    $questionsFilename = "$contentDir/questions_{$sessionData['session']}_{$sessionData['id']}.json";
    	    
    	    if(!file_exists($questionsFilename)) continue;
    	    
    	    $questionsData = json_decode(file_get_contents($questionsFilename), true);
    	    
    	    foreach ($questionsData as $question) 
    	    {
    	        $question['course_session_id'] = $updatedSessionData['id'];
    	        
    	        $questionInstallRet = Question::insert($question);
    	        
    	        if(!$questionInstallRet[SUCCESSFUL])
    	        {
    	            dDie($questionInstallRet);
    	        }
    	    }
        }
	    
	    // Copy images
	    $imageLocation = "$contentDir/img";
	    $this->handleImagesForJSON($allSessionDataCopy, $allUpdatedSessions, $imageLocation, $courseId);
	    
        // Insert Summary Data
        if($summaryData)
        {
            foreach ($summaryData as $summary) 
            {
                Summary::insert($summary);
            }
        }
        
        return [SUCCESSFUL => true, MESSAGE => "{$course['course_code']} installed successfully"];
	}
	
    // Copy images
	private function handleImagesForJSON($oldSessionsData, $updatedSessionsData, $imageLocation, $courseId)
	{
	    $imagesFolder = self::$imagesBaseFolder.$courseId;
	    
	    if(!is_dir($imageLocation)) return;
	    
	    $i = -1;
	    foreach ($oldSessionsData as $oldSession) 
	    {
	        $i++;
	        $updatedSession = $updatedSessionsData[$i];
	        
	        $destinationFolder = "$imagesFolder/{$updatedSession['id']}";
	        
	        $sourceFolder = "$imageLocation/{$oldSession['id']}";
	        $sourceFolder2 = "$imageLocation/{$oldSession['session']}";
	        
	        if(!is_dir($sourceFolder)) {
	            $sourceFolder = $sourceFolder2;
	        }
	        
	        if(!is_dir($sourceFolder)) continue;
	        
	        // If the destination folder is available, skip (means it has already been handled)
	        // else create the folder and copy images
	        if(!is_dir($destinationFolder)){
	            mkdir($destinationFolder, 0777, true);
	        }else continue;
	        
	        \App\Core\Helper::copy($sourceFolder, $destinationFolder);
	    }
	    
	}
	
	private function validateSessions($allSessionData, $contentDir, $courseId)
	{
	    foreach ($allSessionData as $sessionData) 
	    {
	        $sessionData['course_id'] = $courseId;
	        
            $val = CourseSession::validateData($sessionData);
	        
            if(!$val[SUCCESSFUL]) return $val;
            
            $questionsFilename = "$contentDir/questions_{$sessionData['session']}_{$sessionData['id']}.json";
            
            if(!file_exists($questionsFilename))
            {
//                 return [SUCCESSFUL => false, MESSAGE => "Error: Questions file for {$sessionData['session']} not found"];
                continue;
            }
            
            $questionsData = json_decode(file_get_contents($questionsFilename), true);
            
            $questionsVal = $this->validateQuestions($questionsData, $contentDir);

            if(!$questionsVal[SUCCESSFUL]) return $questionsVal;
	    }
	    
	    return [SUCCESSFUL => true, MESSAGE => 'Validation successful'];
	}
	
	private function validateQuestions($questionsData, $contentDir)
	{
	    foreach ($questionsData as $question) 
	    {
	        $ret = Question::validateData($question);
	        
	        if(!$ret[SUCCESSFUL]) return $ret;
	    }
	    
	    return [SUCCESSFUL => true, MESSAGE => 'Validation successful'];
	}
	
	private function validateSummary($summayData, $courseId)
	{
	    foreach ($summayData as $summary)
	    {
	        $summary['course_id'] = $courseId;
	        
	        $ret = Summary::validateData($summary);
	        
	        if(!$ret[SUCCESSFUL]) return $ret;
	    }
	    
	    return [SUCCESSFUL => true, MESSAGE => 'Validation successful'];
	}
		
	
	function unInstallCourse($courseId)
	{
	    $course = Course::where('id', '=', $courseId)->first();
	    
	    if(!$course) return [SUCCESSFUL => false, MESSAGE => htmlentities($courseId).' not found'];
	    
	    $allSessions = CourseSession::where('course_id', '=', $courseId)->get();
	    
	    /** @var CourseSession $session */
	    foreach ($allSessions as $session) 
	    {
// 	        $session->questions()->delete();
	        Question::where('course_session_id', '=', $session['id'])->delete();

	        Instruction::where('course_session_id', '=', $session['id'])->delete();

	        Passage::where('course_session_id', '=', $session['id'])->delete();

	        Summary::where('course_id', '=', $courseId)->delete();
	           
	        $session->delete();
	    }
	    
	    $imagesFolder = APP_DIR."../public/img/content/$courseId";
	    
        if(is_dir($imagesFolder))
        {
            \App\Core\Helper::deleteDir($imagesFolder, true);
        }
        
		$courseFile = $this->coursesFolder.$courseId.self::IS_INSTALLED_SUFFIX;
		if(file_exists($courseFile)){
			rename($courseFile, $this->coursesFolder.$courseId.'.zip');
		}
        
        return [SUCCESSFUL => true, MESSAGE => $course['course_code'].' uninstalled successfully'];
	}
	
	static function deleteImg($courseId, $course_session_id = null, $filename = null) 
	{
	    $file = self::$imagesBaseFolder."$courseId/$course_session_id/$filename";
	    
	    if($course_session_id) $file = "$file/$course_session_id";
	    if($filename) $file = "$file/$filename";
	    
	    if(!file_exists($file)) return;
	    
	    if(is_dir($file)) \App\Core\Helper::deleteDir($file, true);
	    else unlink($file);
	}
	
}
