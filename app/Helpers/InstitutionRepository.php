<?php
namespace App\Helpers;

use App\Models\ExamContent;
use App\Models\Course;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\Institution;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\BaseModel;
use App\Models\InstitutionUser;

class InstitutionRepository
{
    private $formatExam;
    private $examHandler;
    
    public function __construct(
        FormatExam $formatExam,
        ExamHandler $examHandler
    ){
        $this->formatExam = $formatExam;
        $this->examHandler = $examHandler;
    }
 
    function assignInstitutionUser($post) 
    {
        $username = $post['username'];
        $institution = $post['institution'];
        
        $user = User::whereUsername($post)->orWhere('phone', $username)->orWhere('email', $username)->first();
        
        if(!$user) return retF('User not found');
        
        $checkInstitutionUser = InstitutionUser::whereUser_id($user->id)
        ->whereInstitution_id($institution->id)->first();
        
        if($checkInstitutionUser) {
            return retF("User ($user->username) has already been added to this institution ($institution->name)");
        }
        
        $post['user_id'] = $user->id;
        $post['institution_id'] = $institution->id;
        
        $institutionUser = InstitutionUser::create($post);
        
        return retS("User ($user->username) added to this institution ($institution->name)", $institutionUser);
    }
    
    function list($userId = null, $status = 'all', $num = 100, $page = 1, $lastIndex = 0)
    {
        $allRecords = new Institution();
        
        if($userId) $allRecords = $allRecords->where('user_id', '=', $userId);
        
        if(!empty($status) && $status !== 'all') $allRecords = $allRecords->where('status', '=', $status);
        
        if($lastIndex != 0) $allRecords = $allRecords->where('id', '<', $lastIndex);
        
        else $allRecords = $allRecords->skip($num * ($page-1));
        
        $allRecords = $allRecords->orderBy('id', 'DESC')->skip($num * ($page - 1))->take($num)->get();
        
        $count = BaseModel::getCount('institutions');
        
        return [
            'all' => $allRecords,
            'count' => $count,
        ];
    }
    
}

