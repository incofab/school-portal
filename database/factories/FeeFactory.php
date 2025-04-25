<?php

namespace Database\Factories;

use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'title' => fake()
        ->unique()
        ->sentence(),
      'payment_interval' => PaymentInterval::Termly->value,
      'amount' => fake()->numberBetween(10 * 60, 60 * 60),
      'term' => fake()->randomElement(TermType::cases())->value,
      'fee_items' => [
        [
          'title' => fake()->words(2, true),
          'amount' => fake()->randomNumber(2)
        ]
      ],
      'academic_session_id' => AcademicSession::factory()
    ];
  }

  function institution(Institution $institution)
  {
    return $this->state(
      fn($attr) => [
        'institution_id' => $institution->id
      ]
    );
  }

  function feeCategories($count = 2)
  {
    return $this->afterCreating(function (Fee $fee) use ($count) {
      FeeCategory::factory($count)
        ->fee($fee)
        ->create();
    });
  }
}
