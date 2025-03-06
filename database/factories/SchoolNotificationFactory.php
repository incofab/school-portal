<?php

namespace Database\Factories;

use App\Enums\SchoolNotificationPurpose;
use App\Models\Institution;
use App\Models\SchoolNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolNotification>
 */
class SchoolNotificationFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string<\App\Models\SchoolNotification>
   */
  protected $model = SchoolNotification::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $institution = Institution::factory()->create();
    $admin = $institution->createdBy;

    return [
      'institution_id' => $institution->id,
      'purpose' => fake()->randomElement(SchoolNotificationPurpose::cases())
        ->value,
      'description' => fake()->paragraph(),
      'sender_user_id' => $admin->id,
      'reference' => fake()
        ->unique()
        ->word(),
      'receiver_type' => '', // Initially empty, can be filled in states
      'receiver_ids' => [] // Initially empty, can be filled in states
    ];
  }

  /**
   * Indicate that the notification is for all students.
   *
   * @param  integer  $classificationGroupId
   * @return \Illuminate\Database\Eloquent\Factories\Factory
   */
  public function forAllClasses(int $classificationGroupId): Factory
  {
    return $this->state(function (array $attributes) use (
      $classificationGroupId
    ) {
      return [
        'receiver_type' => 'classification-group',
        'receiver_ids' => $classificationGroupId
      ];
    });
  }

  /**
   * Indicate that the notification is for specific classes.
   *
   * @param  array  $classificationIds
   * @return \Illuminate\Database\Eloquent\Factories\Factory
   */
  public function forSpecificClasses(array $classificationIds): Factory
  {
    return $this->state(function (array $attributes) use ($classificationIds) {
      return [
        'receiver_type' => 'classification',
        'receiver_ids' => $classificationIds
      ];
    });
  }
}
