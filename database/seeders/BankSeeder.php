<?php
namespace Database\Seeders;

use App\Core\MonnifyHelper;
use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (Bank::count() > 0) {
      return;
    }
    MonnifyHelper::make()->listBanks();
  }
}
