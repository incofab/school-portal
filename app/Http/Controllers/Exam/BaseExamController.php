<?php
namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;

class BaseExamController extends Controller
{
    
	public function view($view, $data = [], $merged = [])
	{
	    $data['isExam'] = isset($data['isExam']) ? $data['isExam'] : true;
	    
	    return parent::view($view, $data, $merged);
	}
	
	
}