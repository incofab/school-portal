<?php
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
  public function definition(): array
  {
    $courseCodes = ['Engish', 'Maths', 'Economics', 'Biology'];

    // $examContentIDs = \App\Models\ExamContent::all('id')
    //   ->pluck('id')
    //   ->toArray();

    return [
      'course_code' => $this->faker->randomElement($courseCodes),
      //   'exam_content_id' => $faker->randomElement($examContentIDs),
      'category' => $this->faker->word,
      'course_title' => $this->faker->words(7, true),
      'description' => $this->faker->sentence,
      'is_file_content_uploaded' => false
    ];
  }
}
