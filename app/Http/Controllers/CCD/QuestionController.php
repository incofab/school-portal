<?php
namespace App\Http\Controllers\CCD;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSessionQuestionsRequest;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Support\QuestionCourseable;
use App\Support\MorphableHandler;

class QuestionController extends Controller
{
  function index(Institution $institution, QuestionCourseable $morphable)
  {
    $query = $morphable->questions();
    $morphable->loadParent();
    return view('ccd/questions/index', [
      'allRecords' => $query->paginate(100),
      'courseable' => $morphable
    ]);
  }

  function create(Institution $institution, QuestionCourseable $morphable)
  {
    $lastQuestion = $morphable
      ->questions()
      ->latest('question_no')
      ->first();
    $questionNo = intval($lastQuestion?->question_no) + 1;

    return view('ccd/questions/create-question', [
      'edit' => null,
      'questionNo' => $questionNo,
      'courseable' => $morphable
    ]);
  }

  function storeApi(Institution $institution, QuestionCourseable $morphable)
  {
    $data = request()->validate(Question::createRule());
    $this->storeQuestion($institution, $morphable, $data);

    return response()->json(['success' => true]);
  }

  function store(Institution $institution, QuestionCourseable $morphable)
  {
    $data = request()->validate(Question::createRule());
    $this->storeQuestion($institution, $morphable, $data);

    return $this->res(
      successRes('Question created'),
      instRoute('questions.create', [$morphable->getMorphedId()])
    );
  }

  private function storeQuestion(
    Institution $institution,
    QuestionCourseable $morphable,
    array $validatedData = []
  ) {
    $question = $morphable->questions()->updateOrCreate(
      [
        'question_no' => $validatedData['question_no'],
        'institution_id' => $institution->id
      ],
      $validatedData
    );

    return $question;
  }

  function edit(Institution $institution, Question $question)
  {
    return view('ccd/questions/create-question', [
      'edit' => $question,
      'courseable' => $question->courseable,
      'questionNo' => $question->question_no
    ]);
  }

  function update(Institution $institution, Question $question)
  {
    $data = request()->validate(Question::createRule($question));

    $question->fill($data)->save();

    return $this->res(
      successRes('Question record updated'),
      instRoute('questions.index', [
        MorphableHandler::make()->buildIdFromCourseable($question)
      ])
    );
  }

  function destroy(Institution $institution, Question $question)
  {
    dd('Not implemented');
    $question->delete();

    return $this->res(
      successRes('Question record deleted'),
      instRoute('questions.index', [$question->courseable_id])
    );
  }

  function uploadQuestionsView(
    Institution $institution,
    QuestionCourseable $morphable
  ) {
    return view('ccd/questions/upload-session-questions', [
      'courseable' => $morphable
    ]);
  }

  function uploadQuestionsStore(
    Institution $institution,
    QuestionCourseable $morphable,
    UploadSessionQuestionsRequest $uploadSessionQuestionsRequest
  ) {
    $data = $uploadSessionQuestionsRequest->validated();
    foreach ($data['questions'] as $key => $item) {
      $this->storeQuestion($institution, $morphable, $item);
    }

    return redirect(
      instRoute('questions.index', $morphable->getMorphedId())
    )->with('message', 'Questions uploaded successfully');
  }
}
