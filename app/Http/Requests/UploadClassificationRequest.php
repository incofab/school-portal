<?php

namespace App\Http\Requests;

use App\Actions\Sheet\ConvertSheetToArray;
use App\Actions\Sheet\SheetValueHandler;
use App\Enums\TermType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Str;

class UploadClassificationRequest extends FormRequest
{
  public Institution $institution;

  protected function prepareForValidation()
  {
    $this->institution = currentInstitution();
    $this->handleFile();
  }

  protected function handleFile()
  {
    if (!$this->file) {
      return;
    }
    $columnKeyMapping = ['A' => 'title', 'B' => 'student'];
    $prefix = 'course-';
    $courses = $this->institution
      ->courses()
      ->get()
      ->keyBy('code');
    $obj = new ConvertSheetToArray($this->file, $columnKeyMapping);
    $headers = $obj->getRowData(1);
    foreach ($headers as $column => $value) {
      if (empty($value)) {
        continue;
      }
      $course = $courses[$value] ?? null;
      if (!$course) {
        continue;
      }
      $columnKeyMapping[$column] = new SheetValueHandler(
        "$prefix{$course->id}",
        fn($val) => floatval($val)
      );
    }
    $obj->setColumnMapping($columnKeyMapping);

    // Convert the assement dot notation to array
    $excelData = $obj->run();

    /** @var array{
     *  student_id: int,
     *  results: array {
     *    course_id: int,
     *    score: float,
     * }[],
     * }[] $formatedRes */
    $formatedRes = [];
    $students = Student::query()
      ->whereIn('code', array_map(fn($item) => $item['student_id'], $excelData))
      ->get()
      ->keyBy('code');

    foreach ($excelData as $key => $res) {
      $studentCustomId = $res['student_id'];
      $student = $students[$studentCustomId] ?? null;
      if (empty($student)) {
        continue;
      }

      $results = [];
      foreach ($res as $key => $item) {
        if (!Str::startsWith($key, $prefix)) {
          continue;
        }
        $courseId = substr($key, strlen($prefix));
        $results[] = [
          'course_id' => $courseId,
          'score' => $item
        ];
      }

      $formatedRes[] = [
        'student_id' => $student->id,
        'results' => $results
      ];
    }

    $this->merge(['class_results' => $formatedRes]);
  }

  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'institution_id' => ['required'],
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'classification_id' => [
        'required',
        new ValidateExistsRule(Classification::class)
      ],
      'term' => ['required', new Enum(TermType::class)],
      'for_mid_term' => ['nullable', 'boolean'],
      'class_results' => ['required', 'array', 'min:1'],
      'class_results.*.student_id' => ['required', 'integer'],
      'class_results.*.results' => ['required', 'array', 'min:1'],
      'class_results.*.results.*.course_id' => ['required', 'integer'],
      'class_results.*.results.*.score' => ['required', 'numeric', 'min:0']
    ];
  }

  function getRule()
  {
  }
}
