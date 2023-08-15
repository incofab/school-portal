<?php

namespace App\Models;

use App\Support\Queries\QuestionQueryBuilder;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Question extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  public function newEloquentBuilder($query)
  {
    return new QuestionQueryBuilder($query);
  }

  static function createRule(Question $question = null)
  {
    return [
      'question_no' => ['required', 'integer'],
      'question' => ['required', 'string'],
      'option_a' => ['required', 'string'],
      'option_b' => ['required', 'string'],
      'option_c' => ['nullable', 'string'],
      'option_d' => ['nullable', 'string'],
      'option_e' => ['nullable', 'string'],
      'answer' => ['required', 'string'],
      'answer_meta' => ['nullable', 'string'],
      'topic_id' => ['nullable', 'integer', Rule::exists('topics', 'id')]
    ];
  }

  static function multiInsert(CourseSession $courseSession, array $questions)
  {
    $course = $courseSession->course;
    foreach ($questions as $key => $question) {
      $topic = self::handleQuestionTopic($course, $question['topic'] ?? []);
      $courseSession->questions()->firstOrCreate(
        [
          'institution_id' => $courseSession->institution_id,
          'question_no' => $question['question_no']
        ],
        [
          ...collect($question)
            ->only([
              'question',
              'option_a',
              'option_b',
              'option_c',
              'option_d',
              'option_e',
              'answer',
              'answer_meta'
            ])
            ->toArray(),
          'topic_id' => $topic?->id
        ]
      );
    }
  }

  private static function handleQuestionTopic(
    Course $course,
    array|null $topicData
  ) {
    if (empty($topicData) || empty($topicData['title'])) {
      return null;
    }
    $topic = $course->topics()->firstOrCreate(
      [
        'institution_id' => $course->institution_id,
        'title' => $topicData['title']
      ],
      $topicData
    );
    return $topic;
  }

  function topic()
  {
    return $this->belongsTo(Topic::class);
  }

  // CourseSession/CourseTerm
  function courseable()
  {
    return $this->morphTo();
  }
}
