<?php
namespace App\Http\Controllers\CCD;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CourseController extends BaseCCD{
    
    function index($institutionId) 
	{
	    $allRecord = Course::whereInstitution_id($institutionId)->orderBy('id', 'DESC')->get();
	    
	    return  $this->view('ccd.course.index', ['allRecords' => $allRecord]);
	}
	
	function create($institutionId){
        return $this->view('ccd.course.create');
	}
	
	function store($institutionId, Request $request)
	{
	    $request->merge(['institution_id' => $institutionId]);
	    
	    $ret = Course::insert($request->all());
	    
	    if (!$ret[SUCCESSFUL]){
	        return $this->redirect(redirect()->back(), $ret);
	    }
	    
	    return redirect(route('ccd.course.index', $institutionId))->with('message', $ret[MESSAGE]);
	}
	
	function edit($institutionId, $table_id) 
	{
        $course = Course::where('id', '=', $table_id)->firstOrFail();
        
        return $this->view('ccd.course.edit', ['data' => $course]);
	}
	
	function update($institutionId, Request $request, Course $course) 
	{
	    $course->update($request->all());
	    
	    return redirect(route('ccd.course.index', $institutionId))->with('message', 'Subject detail updated');
	}
	
	function delete($institutionId, $table_id) 
	{
	    $course = Course::where('id', '=', $table_id)->firstOrFail();

		if(!$course->canDelete()){
			return redirect()->back()->with('error', 'Cannot delete the course because it has some content');
		}

		$course->delete();
	    
	    return redirect(route('ccd.course.index', $institutionId))->with('message', 'Subject deleted');
	}
	
	
	
}
