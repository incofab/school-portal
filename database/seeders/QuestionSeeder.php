<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Question::factory()
      ->count(1000)
      ->forSeeding()
      ->create();
  }
}
