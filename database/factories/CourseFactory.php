<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
  public function definition(): array
  {
    $course = fake()
      ->unique()
      ->randomElement(self::COURSES);

    return [
      'institution_id' => Institution::factory(),
      'title' => $course,
      'code' => $course,
      'category' => $this->faker->word,
      'description' => $this->faker->sentence,
      'is_file_content_uploaded' => false
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id
      ]
    );
  }
  const COURSES = [
    'Engish',
    'Maths',
    'Economics',
    'Biology',
    'Chemistry',
    'Physics',
    'Economics',
    'Agriculture',
    'Introductory Technology',
    'Fine Arts',
    'Computer Science',
    'Integrated Science',
    'Physical and Health Education',
    'Geography',
    'Government',
    'Literature In English',
    'Technical Drawing',
    'French',
    'Arabic',
    'Igbo',
    'Yoruba',
    'Hausa',
    'CRK',
    'IRK',
    'Music'
  ];
}
