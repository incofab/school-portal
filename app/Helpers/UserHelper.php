<?php
namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\SubscriptionPlan;

class UserHelper{
    
    private $subscriptionHelper;
    private $examHelper;
    
    function __construct(
        \App\Helpers\SubscriptionHelper $subscriptionHelper,
        \App\Helpers\ExamHelper $examHelper
    ){
        $this->subscriptionHelper = $subscriptionHelper;
        $this->examHelper = $examHelper;
    }
    
    function indexPage(User $user)
    {
        $exams = [];
        
        if($user){
            $exams = $this->examHelper->list($user->id, null, 10);
        }
        
        $ret = [
            
            SUCCESSFUL => true,
            MESSAGE => '',
            
            'active_subscriptions' => \App\Helpers\SubscriptionHelper::getUserSubscriptionsByContentId(Auth::user()),
            'subscriptions' => $this->subscriptionHelper->list($user->id, null, true),
            'subscription_plans' => SubscriptionPlan::all(),
            'exam_contents' => $this->examHelper->getAllExamBody(),
            'exam_history' => $exams['all'],
        ];
        
        return $ret;
    }
    
    
    
}