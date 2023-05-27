<?php
namespace App\Helpers;

use Illuminate\Support\Arr;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use App\Models\Deposit;
use App\Models\PaymentReference;
use App\Models\Complaint;
use App\Models\ComplaintReply;

class ComplaintHelper
{    
    function __construct(){
        //
    }
    
    function replyComplaint($post, User $user) 
    {
        $complaint = Complaint::where('id', '=', Arr::get($post, 'complaint_id'))->first();
        
        if(!$complaint) return ret(false, 'Complaint record not found');
        
        $isManager = $user->isManager();
        
        if(!$isManager)
        {
            if($complaint->user_id !== $user->id){
                return ret(false, 'Complaint record not found | Access denied');
            }
        }
        
        $ret = ComplaintReply::insert($post, $user, $complaint);
        
        if(!$ret[SUCCESSFUL]) return $ret;
        
        $complaint['reply_count'] += 1;
        $complaint['last_reply_user_id'] = $user->id;
        $complaint['resolved'] = $isManager;
        $complaint->save();
        
        return $ret;
    }
    
    
}