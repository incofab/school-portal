<?php
namespace Database\Seeders;

use App\Models\AcademicSession;
use Illuminate\Database\Seeder;

class AcademicSessionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $arr = [['title' => '2022/2023'], ['title' => '2023/2024']];
    foreach ($arr as $key => $item) {
      AcademicSession::query()->firstOrCreate($item);
    }
  }
}
