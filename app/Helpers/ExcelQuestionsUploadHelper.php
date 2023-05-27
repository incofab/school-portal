<?php
namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Grade;
use App\Models\CourseSession;
use App\Models\Question;

class ExcelQuestionsUploadHelper
{
    function uploadQuestions($files, $courseSession)
    {
        $courseSessionId = $courseSession->id;
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($files['content']);
        
        $sheetData = $spreadsheet->getActiveSheet();

        $allQuestions = $sheetData->toArray(null, true, true, true);
        
        $colQuestionNo = 'A';
        $colQuestion = 'B';
        $colAnswer = 'C';
        $colOptionA = 'D';
        $colOptionB = 'E';
        $colOptionC = 'F';
        $colOptionD = 'G';
        $colOptionE = 'H';
        
        DB::beginTransaction();
//         dDie($allQuestions);
        foreach ($allQuestions as $rowNo => $rowContent)
        {
//             if($rowNo == '1' || $rowContent == null) continue;
            if($rowNo < 3) continue;

            if(empty($rowContent[$colQuestion]) && empty($rowContent[$colAnswer])) continue;
            
            $arr = [
                'course_session_id' => $courseSessionId,
                'question_no' => $rowContent[$colQuestionNo],
                'question' => $rowContent[$colQuestion],
                'option_a' => $rowContent[$colOptionA],
                'option_b' => $rowContent[$colOptionB],
                'option_c' => $rowContent[$colOptionC],
                'option_d' => $rowContent[$colOptionD],
                'option_e' => $rowContent[$colOptionE],
                'answer' => $rowContent[$colAnswer],
            ];
            
            $ret = Question::validateData($arr);
            
            if(!$ret[SUCCESSFUL])
            {
                if(!empty($ret['is_duplicate'])) continue;
                
                DB::rollBack();
                return $ret;
            }
            
            Question::create($arr);
        }
        
        DB::commit();
        
        return retS('All records saved successfully');
    }
    
}



