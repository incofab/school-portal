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
    $arr = [['title' => '2022/2023']];
    foreach ($arr as $key => $item) {
      AcademicSession::query()->firstOrCreate($item);
    }
  }
}
