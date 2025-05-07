<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'institution_id' => Institution::factory(),
            'institution_user_id' => InstitutionUser::factory(),
            'institution_staff_user_id' => InstitutionUser::factory()->admin(),
            'reference' => Str::orderedUuid(),
            'remark' => $this->faker->sentence(),
            'signed_in_at' => now()->subDay(1),
            'signed_out_at' => now(),
        ];
    } 

    public function institution(Institution $institution)
    {
        return $this->state(function (array $attributes) use ($institution) {
            return [
                'institution_id' => $institution->id,
                'institution_user_id' => InstitutionUser::factory()->withInstitution($institution),
                'institution_staff_user_id' => InstitutionUser::factory()->admin()->withInstitution($institution),
            ];
        });
    }

    public function institutionUser(InstitutionUser $institutionUser)
    {
        return $this->state(function (array $attributes) use ($institutionUser) {
            return [
                'institution_id' => $institutionUser->institution_id,
                'institution_user_id' => $institutionUser->id,
                'institution_staff_user_id' => InstitutionUser::factory()->admin()->withInstitution($institutionUser->institution),
            ];
        });
    }

    /**
     * State to mark an attendance record as signed in only.
     */
    public function signedInOnly()
    {
        return $this->state(function (array $attributes) {
            return [
                'signed_out_at' => null,
            ];
        });
    }

    /**
     * State to mark an attendance record as both signed in and signed out.
     */
    public function signedOut()
    {
        return $this->state(function (array $attributes) {
            return [
                'signed_out_at' => now(),
            ];
        });
    }
}