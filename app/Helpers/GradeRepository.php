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
use App\Models\Grade;

class GradeRepository
{

    function create($post) {
        
        $data = Grade::create($post);
        
        return retS('Data recorded', $data);
    }
 
    function update(Grade $grade, $post) {
        
        $grade->update($post);
        
        return retS('Data updated', $grade);
    }
    
    function delete($id) {
        
        Grade::whereId($id)->delete();
        
        return retS('Record deleted successfully');
    }
    
    function list($institutionId = null, $num = 100, $page = 1, $lastIndex = 0)
    {
        $allRecords = new Grade();
        
        if($institutionId) $allRecords = $allRecords->where('institution_id', '=', $institutionId);
        
        if($lastIndex != 0) $allRecords = $allRecords->where('id', '<', $lastIndex);
        
        else $allRecords = $allRecords->skip($num * ($page-1));
        
        $allRecords = $allRecords->orderBy('id', 'DESC')->skip($num * ($page - 1))->take($num)->get();
        
        $count = BaseModel::getCount('grades', ['institution_id' => $institutionId]);
        
        return [
            'all' => $allRecords,
            'count' => $count,
        ];
    }
    
}

