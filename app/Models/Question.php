<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Question extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'topic_id' => 'integer'
  ];

  static function createRule(Question $question = null, $prefix = '')
  {
    $options = ['A', 'B', 'C', 'D', 'E'];
    return [
      $prefix . 'question_no' => ['required', 'integer'],
      $prefix . 'question' => ['required', 'string'],
      $prefix . 'option_a' => ['required'],
      $prefix . 'option_b' => ['required'],
      $prefix . 'option_c' => ['nullable'],
      $prefix . 'option_d' => ['nullable'],
      $prefix . 'option_e' => ['nullable'],
      $prefix . 'answer' => ['required', Rule::in($options)],
      $prefix . 'answer_meta' => ['nullable', 'string'],
      $prefix . 'topic_id' => [
        'nullable',
        'integer',
        Rule::exists('topics', 'id')
      ]
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
