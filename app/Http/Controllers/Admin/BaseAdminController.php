<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class BaseAdminController extends Controller{

	
	public function view($view, $data = [], $mergedData = [])
	{
	    $data['isAdmin'] = isset($data['isAdmin']) ? $data['isAdmin'] : true;
	    
	    return parent::view($view, $data, $mergedData);
	} 
	
		
	
}