<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Topic;

class TopicSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Topic::factory()
      ->count(20)
      ->create();
  }
}
