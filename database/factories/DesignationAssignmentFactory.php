<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use App\Models\Designation;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\DesignationAssignment> */
class DesignationAssignmentFactory extends Factory
{
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'designation_id' => Designation::factory(),
      'institution_user_id' => InstitutionUser::factory(),
      'classification_id' => null,
      'academic_session_id' => AcademicSession::factory(),
      'assigned_by_user_id' => User::factory(),
      'assigned_at' => now(),
      'ended_at' => null,
      'note' => null
    ];
  }
}
