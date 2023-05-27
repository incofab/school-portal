<?php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Http\Request;

class UserController extends BaseAdminController
{
	function index() {

		$allRecords = User::take(100)->orderBy('id', 'DESC')->get();
		
		return $this->view('admin.user.index', [
				'allRecords' => $allRecords,
    		    'count' => BaseModel::getCount('users'),
		]);
	}
	
	function search(Request $request) 
	{
		$param = $request->input('search_user');
		
		$allRecords = User::where('username', 'LIKE', "%$param%")
		->orWhere('name', 'LIKE', "%$param%")
		->orWhere('email', 'LIKE', "%$param%")
		->orWhere('phone', 'LIKE', "%$param%")
		->orderBy('id', 'DESC')
		->take(100)->get();
		
		return $this->view('admin.user.index', [ 'allRecords' => $allRecords ]);
	}

	function show($table_id_or_username) {
		
	    $userData = User::where('id', '=', $table_id_or_username)
			->orWhere('username', '=', $table_id_or_username)->first();
		
		return $this->view('admin.user.show', ['userData' => $userData]);
	}

	function destroy($table_id) 
	{
	    User::whereId($table_id)->delete();
	    
		return redirect(route('admin.user.index'))->with('message', 'User record deleted');
	}

}





