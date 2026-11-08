<?php
namespace App\Http\Controllers\CCD;

use App\Actions\GenericExport;
use App\Actions\Questions\ConvertTextToQuestions;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionPayloadRequest;
use App\Http\Requests\UploadSessionQuestionsRequest;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Support\QuestionCourseable;
use App\Support\MorphableHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->only([
      'create',
      'storeApi',
      'store',
      'edit',
      'update',
      'destroy',
      'uploadQuestionsView',
      'uploadQuestionsStore',
      'processQuestionSegment'
    ]);
  }

  function index(Institution $institution, QuestionCourseable $morphable)
  {
    $this->authorizeQuestionBank($morphable);
    $query = $morphable->questions();
    $morphable->loadParent();
    return view('ccd/questions/index', [
      'allRecords' => paginateFromRequest($query), //$query->paginate(100),
      'courseable' => $morphable
    ]);
  }

  function create(Institution $institution, QuestionCourseable $morphable)
  {
    $this->authorizeQuestionBank($morphable);
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

  function storeApi(
    Institution $institution,
    QuestionCourseable $morphable,
    QuestionPayloadRequest $request
  ) {
    $this->authorizeQuestionBank($morphable);

    $data = $request->validated();
    $this->storeQuestion($institution, $morphable, $data);

    return response()->json(['success' => true]);
  }

  function store(
    Institution $institution,
    QuestionCourseable $morphable,
    QuestionPayloadRequest $request
  ) {
    $this->authorizeQuestionBank($morphable);
    $data = $request->validated();
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
    $this->authorizeQuestionBank($morphable);
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
    $this->authorizeQuestionBank($question->courseable);
    return view('ccd/questions/create-question', [
      'edit' => $question,
      'courseable' => $question->courseable,
      'questionNo' => $question->question_no
    ]);
  }

  function update(
    Institution $institution,
    Question $question,
    QuestionPayloadRequest $request
  ) {
    $this->authorizeQuestionBank($question->courseable);
    $data = $request->validated();

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
    $this->authorizeQuestionBank($question->courseable);
    $courseSession = $question->courseable;
    $question->delete();

    return $this->res(
      successRes('Question record deleted'),
      instRoute('questions.index', [$courseSession->getMorphedId()])
    );
  }

  function uploadQuestionsView(
    Institution $institution,
    QuestionCourseable $morphable
  ) {
    $this->authorizeQuestionBank($morphable);
    return view('ccd/questions/upload-session-questions', [
      'courseable' => $morphable
    ]);
  }

  function uploadQuestionsStore(
    Institution $institution,
    QuestionCourseable $morphable,
    UploadSessionQuestionsRequest $uploadSessionQuestionsRequest
  ) {
    $this->authorizeQuestionBank($morphable);
    $data = $uploadSessionQuestionsRequest->validated();
    foreach ($data['questions'] as $key => $item) {
      $this->storeQuestion($institution, $morphable, $item);
    }

    return redirect(
      instRoute('questions.index', $morphable->getMorphedId())
    )->with('message', 'Questions uploaded successfully');
  }

  function processQuestionSegment(
    Institution $institution,
    QuestionCourseable $morphable,
    Request $request
  ) {
    $this->authorizeQuestionBank($morphable);
    $validated = $request->validate([
      'segment' => ['required', 'file', 'mimetypes:text/plain', 'max:5120'],
      'idempotency_key' => ['required', 'uuid']
    ]);
    $text = file_get_contents($validated['segment']->getRealPath());
    $plainText = html_entity_decode(
      strip_tags($text),
      ENT_QUOTES | ENT_HTML5,
      'UTF-8'
    );

    if (
      mb_strlen($plainText) >
      UploadSessionQuestionsRequest::MAX_SEGMENT_CHARACTERS
    ) {
      throw ValidationException::withMessages([
        'segment' => 'This segment has exceeded the 2,500-character limit.'
      ]);
    }

    $cacheKey = implode(':', [
      'question-segment-upload',
      $institution->id,
      $morphable->getMorphClass(),
      $morphable->getKey(),
      currentUser()->id,
      $validated['idempotency_key']
    ]);

    if ($cached = Cache::get($cacheKey)) {
      return response()->json($cached);
    }

    return Cache::lock($cacheKey . ':lock', 180)->block(10, function () use (
      $cacheKey,
      $text,
      $institution,
      $morphable
    ) {
      if ($cached = Cache::get($cacheKey)) {
        return response()->json($cached);
      }

      $questions = (new ConvertTextToQuestions())->run($text, [
        // Keep segment requests below Cloudflare's usual origin response limit.
        'timeout' => 85,
        'max_turn_seconds' => 85,
        'retry_attempts' => 0
      ]);
      DB::transaction(function () use ($questions, $institution, $morphable) {
        $lastQuestionNo = (int) $morphable->questions()->max('question_no');
        foreach ($questions as $question) {
          $question['question_no'] = ++$lastQuestionNo;
          $this->storeQuestion($institution, $morphable, $question);
        }
      });

      $result = [
        'success' => true,
        'questions_count' => count($questions)
      ];
      Cache::put($cacheKey, $result, now()->addDay());

      return response()->json($result);
    });
  }

  function downloadQuestions(
    Institution $institution,
    QuestionCourseable $morphable
  ) {
    $this->authorizeQuestionBank($morphable);
    $questions = $morphable
      ->questions()
      ->oldest('question_no')
      ->get();

    $data = $questions->map(
      fn($item) => [
        'question_no' => $item->question_no,
        'question' => $item->question,
        'option_a' => $item->option_a,
        'option_b' => $item->option_b,
        'option_c' => $item->option_c,
        'option_d' => $item->option_d,
        'option_e' => $item->option_e,
        'answer' => $item->answer
      ]
    );

    return (new GenericExport($data, 'questions.xlsx'))->download();
  }

  private function authorizeQuestionBank(QuestionCourseable $morphable): void
  {
    $this->authorize('viewQuestionBank', [
      Course::class,
      $morphable->getCourse()
    ]);
  }
}
