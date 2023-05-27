<?php

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseSession;

class CourseSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CourseSession::factory()->count(70)->create();
    }
}
