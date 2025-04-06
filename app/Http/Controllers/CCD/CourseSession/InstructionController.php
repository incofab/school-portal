<?php
namespace App\Http\Controllers\CCD\CourseSession;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Instruction;
use App\Models\Support\QuestionCourseable;
use App\Support\MorphableHandler;

class InstructionController extends Controller
{
  function index(
    Institution $institution,
    QuestionCourseable $morphable,
    ?Instruction $instruction = null
  ) {
    return view('ccd/course-sessions/instructions', [
      'allRecords' => $morphable->instructions()->paginate(100),
      'courseable' => $morphable,
      'edit' => $instruction
    ]);
  }

  function store(Institution $institution, QuestionCourseable $morphable)
  {
    $data = request()->validate(Instruction::createRule());

    $morphable
      ->instructions()
      ->create([...$data, 'institution_id' => $institution->id]);

    return $this->res(
      successRes('Instruction created'),
      instRoute('instructions.index', [$morphable->getMorphedId()])
    );
  }

  function update(Institution $institution, Instruction $instruction)
  {
    $data = request()->validate(Instruction::createRule($instruction));

    $instruction->fill($data)->save();

    return $this->res(
      successRes('Instruction record updated'),
      instRoute('instructions.index', [
        MorphableHandler::make()->buildIdFromCourseable($instruction)
      ])
    );
  }

  function destroy(Institution $institution, Instruction $instruction)
  {
    $instruction->delete();

    return $this->res(
      successRes('Instruction record deleted'),
      instRoute('instructions.index', [
        MorphableHandler::make()->buildIdFromCourseable($instruction)
      ])
    );
  }
}
