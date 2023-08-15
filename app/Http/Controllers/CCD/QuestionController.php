<?php
namespace App\Http\Controllers\CCD;

use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\Question;

class QuestionController extends Controller
{
  function index(Institution $institution, CourseSession $courseSession)
  {
    $query = $courseSession->questions();

    $courseSession->load('course');
    return view('ccd/questions/index', [
      'allRecords' => $query->with('topic')->paginate(100),
      'courseSession' => $courseSession
    ]);
  }

  function create(Institution $institution, CourseSession $courseSession)
  {
    $lastQuestion = $courseSession
      ->questions()
      ->latest('question_no')
      ->first();
    $questionNo = intval($lastQuestion?->question_no) + 1;

    return view('ccd/questions/create-question', [
      'edit' => null,
      'questionNo' => $questionNo,
      'courseSession' => $courseSession,
      'topics' => $courseSession->course->topics()->get()
    ]);
  }

  function storeApi(Institution $institution, CourseSession $courseSession)
  {
    $this->storeQuestion($institution, $courseSession);

    return response()->json(['success' => true]);
  }

  function store(Institution $institution, CourseSession $courseSession)
  {
    $this->storeQuestion($institution, $courseSession);

    return $this->res(
      successRes('Question created'),
      instRoute('questions.index', [$courseSession])
    );
  }

  private function storeQuestion(
    Institution $institution,
    CourseSession $courseSession
  ) {
    $data = request()->validate(Question::createRule());

    $question = $courseSession->questions()->updateOrCreate(
      [
        'question_no' => $data['question_no'],
        'institution_id' => $institution->id
      ],
      $data
    );

    return $question;
  }

  function edit(Institution $institution, Question $question)
  {
    return view('admin/questions/create-question', [
      'edit' => $question,
      'courseSession' => $question->session,
      'questionNo' => $question->question_no,
      'topics' => $question->session->course->topics()->get()
    ]);
  }

  function update(Institution $institution, Question $question)
  {
    $data = request()->validate(Question::createRule($question));

    $question->fill($data)->save();

    return $this->res(
      successRes('Question record updated'),
      instRoute('questions.index', [$question->course_session_id])
    );
  }

  function destroy(Institution $institution, Question $question)
  {
    dd('Not implemented');
    $question->delete();

    return $this->res(
      successRes('Question record deleted'),
      instRoute('questions.index')
    );
  }
}
