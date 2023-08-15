<?php
namespace App\Http\Controllers\CCD\CourseSession;

use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\Instruction;

class InstructionController extends Controller
{
  function index(
    Institution $institution,
    CourseSession $courseSession,
    Instruction $instruction = null
  ) {
    return view('ccd/course-sessions/instructions', [
      'allRecords' => $courseSession->instructions()->paginate(100),
      'courseSession' => $courseSession,
      'edit' => $instruction
    ]);
  }

  function store(Institution $institution, CourseSession $courseSession)
  {
    $data = request()->validate(Instruction::createRule());

    $courseSession
      ->instructions()
      ->create([...$data, 'institution_id' => $institution->id]);

    return $this->res(
      successRes('Instruction created'),
      instRoute('instructions.index', [$courseSession])
    );
  }

  function update(Institution $institution, Instruction $instruction)
  {
    $data = request()->validate(Instruction::createRule($instruction));

    $instruction->fill($data)->save();

    return $this->res(
      successRes('Instruction record updated'),
      instRoute('instructions.index', [$instruction->course_session_id])
    );
  }

  function destroy(Institution $institution, Instruction $instruction)
  {
    $instruction->delete();

    return $this->res(
      successRes('Instruction record deleted'),
      instRoute('instructions.index', $instruction->course_session_id)
    );
  }
}
