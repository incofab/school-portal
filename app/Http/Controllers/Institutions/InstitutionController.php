<?php
namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\BaseModel;
use Illuminate\Support\Facades\Auth;

class InstitutionController extends Controller
{
  function index($institutionId)
  {
    return $this->view('institution.index', [
      'students_count' => BaseModel::getCount('students', [
        'institution_id' => $institutionId
      ]),

      'events_count' => BaseModel::getCount('events', [
        'institution_id' => $institutionId
      ])
    ]);
  }
}
