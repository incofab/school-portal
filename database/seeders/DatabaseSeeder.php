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
      RoleSeeder::class,
      UserSeeder::class,
      AcademicSessionSeeder::class
      //  CourseSeeder::class,
      //  TopicSeeder::class,
      //  CourseSessionSeeder::class,
      //  QuestionSeeder::class,
      // PriceListSeeder::class
    ]);

    // Funding::factory(5)->create();
  }
}
