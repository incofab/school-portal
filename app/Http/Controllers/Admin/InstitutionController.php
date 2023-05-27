<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Institution;
use App\Helpers\InstitutionRepository;

class InstitutionController extends BaseAdminController
{
    private $institutionRepository;
    
    function __construct(InstitutionRepository $institutionRepository) {
        $this->institutionRepository = $institutionRepository;
    }

	function index()
	{
	    $ret = $this->institutionRepository->list(null, null);
	    
	    return $this->view('admin.institution.index', [ 'allRecords' => $ret['all'] ]);
	}
	
	function create(){
        return $this->view('admin.institution.create');
	}
	
	function store(Request $request)
	{   
	    $ret = Institution::insert($request->all());
	    
	    if(!$ret[SUCCESSFUL]){
	        return $this->redirect(redirect()->back(), $ret);
	    }
	    
	    return redirect(route('admin.institution.index'))->with('message', $ret[MESSAGE]);
	}
	
	function edit($id)
	{
	    $institution = Institution::whereId($id)->firstOrFail();
	    
        return $this->view('admin.institution.edit', ['data' => $institution]);
	}
	
	function update(Request $request, Institution $institution)
	{
	    $institution->update($request->all());
	    
	    return redirect(route('admin.institution.index'))->with('message', 'Record updated');
	}
	
	function destroy($tableID)
	{
	    Institution::whereId($tableID)->delete();
	    
	    return redirect(route('admin.institution.index'))->with('message', 'Delete institution');
	}
	
	function assignUserView($id)
	{
	    $institution = Institution::whereId($id)->firstOrFail();
	    
        return $this->view('admin.institution.assign-user', ['data' => $institution]);
	}
	
	function assignUserStore(Request $request, $id)
	{
	    $institution = Institution::whereId($id)->firstOrFail();
	    
	    $request->merge([
	        'institution' => $institution,
	        'institution_id' => $institution->id,
	    ]);
	    
	    $ret = $this->institutionRepository->assignInstitutionUser($request->all());
	    
	    if(!$ret['success']) return $this->redirect(redirect()->back(), $ret);
	    
	    return redirect(route('admin.institution.index'))->with('message', $ret['message']);
	}
	
}