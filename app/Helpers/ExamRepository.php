<?php
namespace App\Helpers;

use App\Models\ExamContent;
use App\Models\Course;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;
use App\Models\Exam;
use App\Models\ExamSubject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\Institution;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\BaseModel;

class ExamRepository
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
    
    function getAllExamBody() 
    {
        $all = ExamContent::all(['id', 'exam_name', 'fullname', 'is_file_content_uploaded', 'description']);
        
        return $all;
    }
    
    function getCoursesWithSessions($examBodyId) 
    {
        $courses = Course::where('exam_content_id', '=', $examBodyId)->with(['sessions','topics'])->get();
        
        return $courses;
    }
    
    function registerExam($eventDataOrID, $studentId,
        $selectedSubjectSessionIDs,
        Institution $institution
    ){
        if($eventDataOrID instanceof Event)
        {
            $event = $eventDataOrID;
        }
        else
        {
            $event = Event::whereId($eventDataOrID)->first();
            
            if (!$event) return retF('Event not found');
            
            if ($event['institution_id'] != $institution['id']){
                return retF('This event does not belong to this center');
            }
        }
        
        $student = Student::whereInstitution_id($institution->id)->whereStudent_id($studentId)->first();
        
        if(!$student) return retF('Student record not found in this institution');
        
        $post = [
            'student_id' => $studentId,
            'event_id' => $event->id,
            'duration' => $event->duration,
            'time_remaining' => $event->duration,
        ];
        
        DB::beginTransaction();
        
        $ret = Exam::insert($post);
        
        if (!$ret[SUCCESSFUL]) return $ret;
        
        /** @var \App\Models\Exam $exam */
        $exam = $ret['data'];
        
        ExamSubject::multiSubjectInsert($selectedSubjectSessionIDs, $exam);
        
        DB::commit();
        
        return retS('Exam registered');
    }
    
    function pauseExam($examNo)
    {
        $exam = Exam::whereExam_no($examNo)->with('student')->first();
        
        if (!$exam) return retF('Exam record not found');
        
        if($exam['status'] != STATUS_ACTIVE) return retF('Exam inactive');
        
        if(!$exam['end_time']){
            return retF('Exam has no end time');
        }
        elseif (Carbon::parse($exam['end_time'])->getTimestamp() < Carbon::now()->getTimestamp())
        {
            $this->endExam($examNo);
            
            return retF('Time elapsed');
        }
        
        $exam['status'] = STATUS_PAUSED;
        
        $exam['pause_time'] = Carbon::now()->toDateTimeString();
        
        $exam['end_time'] = null;
        
        $exam->save();
        $this->examHandler->syncExamFile($exam);
        
        return retS('Exam paused');
    }
    
    function pauseSelectedExam(Exam $exam)
    {
        if (!$exam) return retF('Exam record not found');
        
        if($exam['status'] != STATUS_ACTIVE) return retF('Exam inactive');
        
        if(!$exam['end_time']) return retF('Exam has no end time');
        
        $exam['status'] = STATUS_PAUSED;
        
        $exam['pause_time'] = Carbon::now()->toDateTimeString();
        
        $exam['end_time'] = null;
        
        $exam->save();
        
        $this->examHandler->syncExamFile($exam);
        
        return retS('Exam paused');
    }
    
    function endExam($examNo)
    {
        /** @var Exam $exam */
        $exam = Exam::whereExam_no($examNo)->with(['examSubjects', 'event', 'student'])->first();
        
        if (!$exam) return retF('Exam record not found');
        
        $examSubjects = $exam['examSubjects'];
        $studentData = $exam['student'];
        
        if($exam['status'] == STATUS_ENDED) return retF('Exam already submitted');
        
        $totalScore = 0;
        $totalNumOfQuestions = 0;
        
        /** @var \App\Models\ExamSubject $examSubject */
        foreach ($examSubjects as $examSubject)
        {
            $questions = \App\Models\Question::
            where('course_session_id', '=', $examSubject['course_session_id'])->get(['id', 'answer']);
            
            $scoreDetail = $this->examHandler->calculateScoreFromFile($exam, $examSubject['id'], $questions);
            
            $score = $scoreDetail['score'];//$questionAttemptsModel->getScore($examSubject[TABLE_ID]);
            
            $numOfQuestions = $scoreDetail['num_of_questions'];//$questionsModel->getNumOfQuestions($examSubject[COURSE_CODE], $examSubject[COURSE_SESSION_ID]);
            
            $examSubject['score'] = $score;
            
            $examSubject['num_of_questions'] = $numOfQuestions;
            
            $examSubject['status'] = STATUS_ENDED;
            
            $examSubject->save();
            
            $totalScore += $score;
            
            $totalNumOfQuestions += $numOfQuestions;
            //             dlog("totalScore = $totalScore");
        }
        
        $exam['status'] = STATUS_ENDED;
        $exam['score'] = $totalScore;
        $exam['num_of_questions'] = $totalNumOfQuestions;
        
        $exam->save();
        $this->examHandler->syncExamFile($exam);
        
        return retS('Exam ended');
    }
    
    function startExam($examNo)
    {
        $exam = Exam::whereExam_no($examNo)->with(['student', 'event'])->first();
        
        if (!$exam) return retF('Exam record not found');
        
        if($exam['status'] != STATUS_ACTIVE && $exam['status'] != STATUS_PAUSED){
            return retF('Exam inactive');
        }
        
        $event = $exam['event'];
        
        if($event['status'] != STATUS_ACTIVE){
            return retF("{$event[TITLE]} is not started yet");
        }
        
        if ($exam['end_time'] && (Carbon::parse($exam['end_time'])->getTimestamp() < Carbon::now()->getTimestamp()))
        {
            $this->endExam($examNo);
            
            return retF('Time elapsed, Exam ended');
        }
        
        $examSubjects = ExamSubject::whereExam_no($examNo)->with(['course', 'session'])->get();
        
        $arr = [];
        
        $contentRet = $this->examHandler->getContent($exam['event_id'], $exam['exam_no'], false);
        
        $allQuestionAttempts = $contentRet[SUCCESSFUL] ? $contentRet['content']['attempts'] : null;
        
        /** @var \App\Models\ExamSubject $examSubject */
        foreach ($examSubjects as $examSubject)
        {
            $subject = $examSubject['course'];
            $session = $examSubject['session'];
            
            $questions = Question::where('course_session_id', '=', $session['id'])->get();
            
            $questionsFormated = [];
            
            foreach ($questions as $question)
            {
                $qArr = $question;
                
                unset($qArr['answer']);
                unset($qArr['answer_meta']);
                
                $qArr['question_id'] = $question['id'];
                $questionsFormated[] = $qArr;
            }
            
            $questionAttempts = Arr::get($allQuestionAttempts, $examSubject['id'], []);
            
            $passages = Passage::where('course_session_id', '=', $session['id'])->get();
            
            $instructions = Instruction::where('course_session_id', '=', $session['id'])->get();
            
            $subjectDataFormatted = [
                'exam_subject_id' => $examSubject['id'],
                'session_id' => $session['id'],
                'course_id' => $subject['id'],
                'course_code' => $subject['course_code'],
                'course_title' => $subject['course_title'],
                'year' => $session['session'],
                'general_instructions' => $session['general_instructions'],
                'instructions' => $instructions->toArray(),
                'passages' => $passages->toArray(),
                'attempted_questions' => $questionAttempts,
                'questions' => $questionsFormated,
            ];
            
            $arr['all_exam_subject_data'][] = $subjectDataFormatted;
            //             dlog($attemptedQuestionsFormated);
        }
        
        if($exam['status'] == STATUS_PAUSED)
        {
            $timeElapsed = Carbon::parse($exam['start_time'])
            ->diffInSeconds(Carbon::parse($exam['pause_time']), false);
            
            $timeRemaining = $event['duration'] - $timeElapsed;
            
            if ($timeRemaining < 2)
            {
                $this->endExam($examNo);
                
                return retF('Exam was paused when time has already elapsed');
            }
            
            $exam['start_time'] = Carbon::now()->toDateTimeString();
            
            $exam['end_time'] = Carbon::parse($exam['start_time'])->addSeconds($timeRemaining)->toDateTimeString();
            
            $exam['status'] = STATUS_ACTIVE;
        }
        elseif(empty($exam['start_time']))
        {
            $exam['start_time'] = Carbon::now()->toDateTimeString();
            
            $exam['end_time'] = Carbon::parse($exam['start_time'])
            ->addSeconds($event['duration'])->toDateTimeString();
        }
        
        $exam->save();
        $this->examHandler->syncExamFile($exam);
        
        $arr['meta']['time_remaining'] = Carbon::now()->diffInSeconds(Carbon::parse($exam['end_time']), false);
        $arr['exam'] = $exam->toArray();
        
        return [SUCCESSFUL => true, MESSAGE => 'Exam started', 'data' => $arr, 'exam' => $exam];
    }
    
    function extendExam(\App\Models\Exam $exam, $mins)
    {
        if($mins < 1) return retF("Set a valid extension time");
        
        if($exam['status'] !== STATUS_PAUSED && empty($exam['end_time'])) {
            return retF("Exam has not started yet");
        }
        
        $currentEndTime = \Carbon\Carbon::parse($exam['end_time']);
        
        if($exam['status'] === STATUS_ENDED || \Carbon\Carbon::now()->diffInSeconds($currentEndTime, false) < 10)
        {
            $exam['time_remaining'] = 0;
            $currentEndTime = \Carbon\Carbon::now();
            $exam['status'] = STATUS_ACTIVE;
        }
        
        if($exam['status'] === STATUS_PAUSED && !empty($exam['pause_time']))
        {
            $pauseTime = \Carbon\Carbon::parse($exam['pause_time']);
            
            $exam['pause_time'] = $pauseTime->subMinute($mins);
        }
        else
        {
            $exam['end_time'] = $currentEndTime->addMinute($mins);
        }
        
        $exam['time_remaining'] += $mins * 60;
        $exam->save();
        
        $this->examHandler->syncExamFile($exam);
        
        return retS("Exam time has been extende by {$mins}mins. Student should refresh his/her page.");
    }
    
    
    
    function list($userId = null, $status = 'all', $num = 100, $page = 1, $lastIndex = 0)
    {
        $allRecords = new Exam();
        
        if($userId) $allRecords = $allRecords->where('user_id', '=', $userId);
        
        if(!empty($status) && $status !== 'all') $allRecords = $allRecords->where('status', '=', $status);
        
        if($lastIndex != 0) $allRecords = $allRecords->where('id', '<', $lastIndex);
        
        else $allRecords = $allRecords->skip($num * ($page-1));
        
        $allRecords = $allRecords->with(['examSubjects', 'examSubjects.course', 'user'])
        ->orderBy('id', 'DESC')
        ->skip($num * ($page - 1))->take($num)->get();
        
        $count = Exam::getCount('exams');
        
        return [
            'all' => $allRecords,
            'count' => $count,
        ];
    }
    
}

