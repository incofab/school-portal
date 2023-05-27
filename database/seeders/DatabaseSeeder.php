<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
//             ExamContentSeeder::class,
//             CourseSeeder::class,
//             TopicSeeder::class,
//             CourseSessionSeeder::class,
//             QuestionSeeder::class,
        ]);
    }
}
