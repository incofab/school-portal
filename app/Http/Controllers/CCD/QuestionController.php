<?php
namespace App\Http\Controllers\CCD;

use App\Models\CourseSession;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\Student;

class QuestionController extends BaseCCD
{
    
    function index($institutionId, $session_id)
    {
        $courseSession = CourseSession::whereId($session_id)->with('course', 'questions')->firstOrFail();
        
        return  $this->view('ccd.question.index', [
            'questions' => $courseSession['questions'],
            'course' => $courseSession['course'],
            'session' => $courseSession
        ]);
        
    }
    
    function create($institutionId, $session_id)
    {
        $courseSession = CourseSession::whereId($session_id)->with('course', 'questions')->firstOrFail();
        
        return $this->view('ccd.question.create', [
            'questions' => $courseSession['questions'],
            'course' => $courseSession['course'],
            'session' => $courseSession
        ]);
    }
    
    function store($institutionId, $sessionId, Request $request)
    {
        $request->merge(['course_session_id' => $sessionId]);
        
        $ret = Question::insert($request->all());
        
        if(!$ret[SUCCESSFUL]) return $this->redirect(redirect()->back(), $ret);
        
        return redirect(route('ccd.question.index', [$institutionId, $sessionId]))->with('message', $ret[MESSAGE]);
    }
    
    function apiCreate($institutionId, $sessionId, Request $request)
    {
        $request->merge(['course_session_id' => $sessionId]);
        
        $ret = Question::insert($request->all());
        
        return response()->json($ret);
    }
    
    function edit($institutionId, $table_id)
    {
        $question = Question::whereId($table_id)->with('session', 'session.course')->firstOrFail();
        
        return $this->view('ccd.question.edit', [
            'question' => $question,
            'session' => $question['session'],
            'course' => $question['session']['course']
        ]);
    }
    
    function update($institutionId, Request $request, Question $question)
    {
        $question->update($request->all());
        
        if($request->input('goto_next'))
        {
            $nextQuestion = Question::where('id', '>', $question->id)->first();
            
            if(!$nextQuestion){
                return redirect(route('ccd.question.index', [$institutionId, $question->course_session_id]))
                ->with('message', 'Question updated');
            }
            
            return redirect(route('ccd.question.edit', [$institutionId, $nextQuestion->id]))
            ->with('message', 'Question updated');
        }
        
        return redirect(route('ccd.question.index', [$institutionId, $question->course_session_id]))
        ->with('message', 'Question updated');
    }
    
    function destroy($institutionId, $table_id)
    {
        $question = Question::whereId($table_id)->firstOrFail();
        
        $question->delete();
        
        return redirect(route('ccd.question.index', [$institutionId, $question->course_session_id]))
        ->with('message', 'Question Deleted');
    }
    
    function show($institutionId, $table_id)
    {
        $question = Question::whereId($table_id)->with(['session', 'session.course'])->firstOrFail();
        
        return $this->view('ccd.question.show', [
            'session' => $question['session'],
            'course' => $question['session']['course'],
            'question' =>  $question,
        ]);
        
    }
    
    
    
}
