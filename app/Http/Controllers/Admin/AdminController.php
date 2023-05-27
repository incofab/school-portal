<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Institution;

class AdminController extends BaseAdminController
{

	function index()
	{
	    return $this->view('admin.index');
	}
	
}