<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplateUtility;

class DummyController extends Controller
{
  public function __construct()
  {
  }

  function sendWhatsappMessage()
  {
    dd('blocked');
    $res = (new WhatsappTemplateUtility(
      receiverPhoneNumber: '2347036098561',
      receiverName: 'Incofab',
      message: 'Hello, this is a test message from Edumanager!',
      schoolName: 'Test School'
    ))->send();

    return response()->json($res->toArray());
  }

  function updateGrades(Institution $institution)
  {
    $courseResults = $institution->courseResults()->get();
    foreach ($courseResults as $courseResult) {
      $grade = \App\Actions\CourseResult\GetGrade::run(
        $courseResult->result,
        $courseResult->classification_id,
        $courseResult->for_mid_term ?? false
      );
      // echo "courseResultId = {$courseResult->id}, Result = {$courseResult->result}, Grade = $grade";
      $courseResult->grade = $grade;
      $courseResult->save();
    }
    return response()->json([
      'status' => 'success',
      'message' => $courseResults->count() . ' Grades updated successfully',
      'data' => null
    ]);
  }
}
