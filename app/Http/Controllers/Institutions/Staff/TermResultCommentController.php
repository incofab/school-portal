<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TermResult;
use Illuminate\Http\Request;

class TermResultCommentController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function teacherComment(
    Request $request,
    Institution $institution,
    TermResult $termResult
  ) {
    $data = $request->validate(['comment' => ['required', 'string']]);
    $termResult->fill(['teacher_comment' => $data['comment']])->save();
    return $this->ok();
  }

  public function principalComment(
    Request $request,
    Institution $institution,
    TermResult $termResult
  ) {
    $data = $request->validate(['comment' => ['required', 'string']]);
    $termResult->fill(['principal_comment' => $data['comment']])->save();
    return $this->ok();
  }
}
