<?php

namespace App\Http\Controllers\Institutions\Results;

use App\Actions\CourseResult\TermResultDeleteHandler;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TermResult;
use Illuminate\Http\Request;

class DeleteTermResultController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    TermResult $termResult
  ) {
    (new TermResultDeleteHandler($termResult))->delete();
    return $this->ok();
  }
}
