<?php
namespace Database\Seeders;

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
      AcademicSessionSeeder::class
      //             CourseSeeder::class,
      //             TopicSeeder::class,
      //             CourseSessionSeeder::class,
      //             QuestionSeeder::class,
    ]);
  }
}
