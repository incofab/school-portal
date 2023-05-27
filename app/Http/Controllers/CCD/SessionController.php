<?php
namespace App\Http\Controllers\CCD;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Http\Request;
use App\Repositories\CourseSessionRepository;
use App\Helpers\ExcelQuestionsUploadHelper;

class SessionController extends BaseCCD{
    
    private $courseSessionRepository;
    
    function __construct(CourseSessionRepository $courseSessionRepository) {
        $this->courseSessionRepository = $courseSessionRepository;
    }
    
    function index($institutionId, $courseId)
    {
        $course = Course::whereId($courseId)->firstOrFail();
        
        $allCoursesYears =  CourseSession::whereCourse_id($courseId)->get();
        
        return  $this->view('ccd.session.index', [
            'allRecords' => $allCoursesYears,
            'course' => $course
        ]);
    }
    
    function create($institutionId, $courseId){
        return $this->view('ccd.session.create', ['courseId' => $courseId]);
    }
    
    function store($institutionId, $courseId, Request $request)
    {
        $request->merge(['course_id' => $courseId]);
        
        $ret = CourseSession::insert($request->all());
        
        if(!$ret[SUCCESSFUL]) return $this->redirect(redirect()->back(), $ret);
        
        return redirect(route('ccd.session.index', [$institutionId, $courseId]))
        ->with('message', $ret[MESSAGE]);
    }
    
    function edit($institutionId, $session_id)
    {
        $session = CourseSession::where('id', '=', $session_id)->with('course')->firstOrFail();
        
        return $this->view('ccd.session.update', ['data' => $session]);
    }
    
    function update($institutionId, Request $request, CourseSession $courseSession)
    {
        $courseSession->update($request->all());
        
        return redirect(route('ccd.session.index', [$institutionId, $courseSession->id]))->with('message', 'Session updated');
    }
    
    function delete($institutionId, $session_id)
    {
        CourseSession::whereId($session_id)->deleted();
        
        return redirect(route('ccd.session.index', [$institutionId, $session_id]))->with('message', 'Session deleted');
    }
    
    function preview($institutionId, $session_id)
    {
        $sessionDetails = CourseSession::where('id', '=', $session_id)
        ->with(['course', 'questions', 'instructions', 'passages', 'questions'])->firstOrFail();
        
        return $this->view('ccd.session.show', [
            'session' => $sessionDetails,
            'course' => $sessionDetails->course,
            'allCourseYearQuestions' => $sessionDetails->questions,
        ]);
    }
    // TODO: Create a middleware to protect institution from accessing other peoples' content  
    function uploadExcelQuestionCreate($institutionId, $courseId, $courseSessionId){
        return $this->view('ccd.session.upload-excel-questions', [
//             'courseSession' => $this->courseSessionRepository->show($institutionId, $courseSessionId),
            'courseSession' => CourseSession::whereId($courseSessionId)->whereCourse_id($courseId)->with('course')->firstOrFail(),
        ]);
    }
    
    function uploadExcelQuestionStore($institutionId, $courseId, $courseSessionId, 
        Request $request, ExcelQuestionsUploadHelper $excelQuestionsUploadHelper
    ){
        $courseSession = CourseSession::whereId($courseSessionId)->whereCourse_id($courseId)->firstOrFail();
        
        $ret = $excelQuestionsUploadHelper->uploadQuestions($request->all(), $courseSession);
        
        if(!$ret[SUCCESSFUL]) return $this->redirect(redirect()->back(), $ret);
        
        return redirect(route('ccd.session.index', [$institutionId, $courseId]))
        ->with('message', $ret[MESSAGE]);
    }
}
