<?php
namespace App\Helpers;

class ExamHandler
{
    const EXAM_TIME_ALLOWANCE = 100; // 100 seconds
    
    function __construct()
    {
    }
    
    /**
     * This creates an exam file if it doesn't exits or updates it
     * @param \App\Models\Exam $exam
     * @return boolean[]|string[]
     */
    function syncExamFile($exam)
    {
        $file = $this->getFullFilepath($exam['event_id'], $exam['exam_no'], true);
        
        $examFileContent = file_exists($file) ?
        json_decode(file_get_contents($file), true) : null;
        
        // If it's not empty, then the exam has just been restarted
        if(empty($examFileContent))
        {
            $examFileContent = [
                'exam' => $exam,
                'attempts' => [],
            ];
        }
        else
        {
            $examFileContent['exam'] = $exam;
        }
        
        $ret = file_put_contents($file, json_encode($examFileContent, JSON_PRETTY_PRINT));
        
        if($ret === false)
        {
            return ['success' => false, 'message' => 'Exam file failed to create'];
        }
        
        return ['success' => true, 'message' => 'Exam file ready'];
    }
    
    function getContent($eventId, $examNo, $checkTime = true)
    {
        $file = $this->getFullFilepath($eventId, $examNo, false);
        
        if(!file_exists($file))
        {
            return ['success' => false, 'message' => 'Exam file not found', 'exam_not_found' => true];
        }
        
        $examFileContent = json_decode(file_get_contents($file), true);
        
        if(empty($examFileContent))
        {
            return ['success' => false, 'message' => 'Exam file not found', 'exam_not_found' => true];
        }
        
        /************Check Exam Time**************/
        if($checkTime)
        {
            $exam = $examFileContent['exam'];
            $currentTime = time();
            $endTime = strtotime($exam['end_time']) + self::EXAM_TIME_ALLOWANCE;
            
            if($currentTime > $endTime)
            {
                return ['success' => false, 'message' => 'Time Elapsed', 'time_elapsed' => true, 'content' => $examFileContent];
            }
        }
        /*//***********Check Exam Time**************/
        
        return ['success' => true, 'message' => '', 'content' => $examFileContent, 'file' => $file];
    }
    
    function attemptQuestion(array $studentAttempts, $eventId, $examNo)
    {
        $ret1 = $this->getContent($eventId, $examNo);
        
        if($ret1['success'] !== true) return $ret1;
        
        //         $studentAttempts = $post['attempts'];
        $examFileContent = $ret1['content'];
        $file = $ret1['file'];
        $savedAttempts = $examFileContent['attempts'];
        
        foreach ($studentAttempts as $studentAttempt)
        {
            $subjectId = $studentAttempt['exam_subject_id'];
            $questionId = $studentAttempt['question_id'];
            
            if(!isset($savedAttempts[$subjectId]))
            {
                $savedAttempts[$subjectId] = [];
            }
            
            $savedAttempts[$subjectId][$questionId] = $studentAttempt;
        }
        
        $examFileContent['attempts'] = $savedAttempts;
        
        $ret = file_put_contents($file, json_encode($examFileContent, JSON_PRETTY_PRINT));
        
        if($ret === false)
        {
            return ['success' => false, 'message' => 'Exam file failed to recorded attempt'];
        }
        
        return ['success' => true, 'message' => 'Exam file, question attempt recorded'];
    }
    
    function calculateScoreFromFile(
        $exam,
        $examSubjectId,
        $questions
    ){
        $contentRet = $this->getContent($exam['event_id'], $exam['exam_no'], false);
        
        if(!$contentRet['success']) return $contentRet;
        
        $size = count($questions);
        
        $examFileContent = $contentRet['content'];
        
        if(empty($examFileContent) || empty($examFileContent['attempts'][$examSubjectId]))
        {
            return ['success' => true, 'score' => 0, 'num_of_questions' => $size];
        }
        
        $score = 0;
        $subjectAttempts = $examFileContent['attempts'][$examSubjectId];
        
        foreach ($questions as $question)
        {
            if(!empty($subjectAttempts[$question['id']]['attempt']))
            {
                if($question['answer'] === $subjectAttempts[$question['id']]['attempt'])
                {
                    $score++;
                }
            }
        }
        
        return ['success' => true, 'score' => $score, 'num_of_questions' => $size];
    }
    
    private function getFullFilepath($eventId, $examNo, $toCreateBaseFolder = true)
    {
        $ext = "edr";
        
        $filename = "exam_$examNo";
        
        $examFolderName = "event_$eventId";
        
        $baseFolder = APP_DIR."../public/exams/$examFolderName";
        
        if(!file_exists($baseFolder) && $toCreateBaseFolder)
        {
            mkdir($baseFolder, 0777, true);
        }
        
        return "$baseFolder/$filename.$ext";
    }
    
}



