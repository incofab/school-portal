<?php
namespace Database\Seeders;

use App\Enums\ManagerRole;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $this->registerAdmin();
  }

  private function registerAdmin()
  {
    $adminEmail = config('app.admin.email') ?? 'admin@email.com';
    $data = [
      'email' => $adminEmail,
      'first_name' => 'Admin',
      'last_name' => 'Admin',
      'other_names' => '',
      'phone' => '08033334444',
      'email_verified_at' => now(),
      'manager_role' => ManagerRole::Admin,
      'password' =>
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' // password
    ];

    User::firstOrCreate(['email' => $adminEmail], $data);
  }
}
