<?php

use Illuminate\Database\Seeder;
use App\Models\ExamContent;

class ExamContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ExamContent::factory()->count(3)->create();
    }
}
