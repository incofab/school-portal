<?php

namespace Database\Factories;

use App\Enums\FaqType;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FaqFactory extends Factory
{
  protected $model = Faq::class;

  public function definition(): array
  {
    $name = fake()->sentence(6, true);

    return [
      'name' => $name,
      'type' => FaqType::Faq->value,
      'code' =>
        Str::slug($name) .
        '-' .
        fake()
          ->unique()
          ->numberBetween(1000, 9999),
      'description' => '<p>' . fake()->paragraph() . '</p>',
      'video_url' => null,
      'is_active' => true,
      'sort_order' => fake()->numberBetween(1, 100)
    ];
  }

  public function faq(): static
  {
    return $this->state(['type' => FaqType::Faq->value]);
  }

  public function knowledgeBase(): static
  {
    return $this->state(['type' => FaqType::KnowledgeBase->value]);
  }
}
