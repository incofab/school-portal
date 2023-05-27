<?php
namespace App\Http\Controllers\Home;

use App\Models\Complaint;
use App\Models\BaseModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ComplaintController extends Controller
{
    private $emailSender;
    private $complaintHelper;
    protected $numPerPage = 50;
    
    function __construct(
        \App\Core\EmailSender $emailSender,
        \App\Helpers\ComplaintHelper $complaintHelper
    ){
        $this->emailSender = $emailSender;
        $this->complaintHelper = $complaintHelper;
    }
    
    function apiStoreComplaint(Request $request)
    {
        $ret = Complaint::insert($request->all(), Auth::user());
        
        die(json_encode($ret));
    }
    
    
    
}





