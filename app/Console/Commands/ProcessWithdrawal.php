<?php

namespace App\Console\Commands;

use App\Models\Withdrawal;
use Illuminate\Console\Command;

class ProcessWithdrawal extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:process-withdrawal';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Automatically process withdrawals';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $withdrawals = Withdrawal::query()
      ->isProcessed(false)
      ->take(10)
      ->get();
    foreach ($withdrawals as $key => $withdrawal) {
      //
    }
  }
}
