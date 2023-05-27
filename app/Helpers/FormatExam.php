<?php
namespace App\Helpers;

use App\Models\Question;
use Illuminate\Support\Arr;
use App\Models\Passage;
use App\Models\Instruction;

class FormatExam
{
    
    function formatExamSubjects($examSubjects, $allQuestionAttempts) 
    {
        $arr = [];
        
        /** @var \App\Models\ExamSubject $examSubject */
        foreach ($examSubjects as $examSubject)
        {
            $subject = $examSubject['course'];
            $session = $examSubject['session'];
            
            $questions = Question::where('course_session_id', '=', $session['id'])
//             ->where('course_code', '=', $subject['course_code'])
            ->get();
            
            $questionsFormated = [];
            $attemptedQuestionsFormated = [];
            
            $count = 1;
            foreach ($questions as $question)
            {
                if($count > $examSubject['num_of_questions']) break;
                
                $count++;
                
                $questionsFormated[] = [
                    'id' => $question['id'],
                    'question_id' => $question['id'],
                    'question_no' => $question['question_no'],
                    'question' => $question['question'],
                    'option_a' => $question['option_a'],
                    'option_b' => $question['option_b'],
                    'option_c' => $question['option_c'],
                    'option_d' => $question['option_d'],
                    'option_e' => $question['option_e'],
                ];
            }
            
            $questionAttempts = Arr::get($allQuestionAttempts, $examSubject['id'], []);
            
            foreach ($questionAttempts as $questionAttempt)
            {
                $attemptedQuestionsFormated[$questionAttempt['question_id']] = [
                    'question_id' => $questionAttempt['question_id'],
                    'attempt' => $questionAttempt['attempt'],
                ];
            }
            
            $passages = Passage::where('course_session_id', '=', $session['id'])->get();
            
            $instructions = Instruction::where('course_session_id', '=', $session['id'])->get();
            
            $subjectDataFormatted = [
                'exam_subject_id' => $examSubject['id'],
                'session_id' => $session['id'],
                'course_code' => $subject['course_code'],
                'course_id' => $subject['id'],
                'course_title' => $subject['course_title'],
                'year' => $session['session'],
                'general_instructions' => $session['general_instructions'],
                'instructions' => $instructions->toArray(),
                'passages' => $passages->toArray(),
                'attempted_questions' => $attemptedQuestionsFormated,
                'questions' => $questionsFormated,
            ];
            
            $arr['all_exam_subject_data'][] = $subjectDataFormatted;
            //             dlog($attemptedQuestionsFormated);
        }
        
        return $arr;
    }
    
    static function getPassage($allPassages, $QuestionNo) {
        foreach ($allPassages as $passage) {
            if($QuestionNo >= $passage[FROM_] && $QuestionNo <= $passage[TO_])
                return $passage;
        }
        return null;
    }
    
    static function getInstruction($allInstructions, $QuestionNo) {
        foreach ($allInstructions as $instruction) {
            
            if($QuestionNo >= $instruction[FROM_] && $QuestionNo <= $instruction[TO_]){
                
                return $instruction;
            }
        }
        return null;
    }
}

