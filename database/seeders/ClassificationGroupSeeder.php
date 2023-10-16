<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClassificationGroup;

class ClassificationGroupSeeder extends Seeder
{
    public function run()
    {
      ClassificationGroup::create(['institution_id' => 1, 'title' => 'JSS 1']);
      ClassificationGroup::create(['institution_id' => 1, 'title' => 'JSS 2']);
      ClassificationGroup::create(['institution_id' => 1, 'title' => 'JSS 3']);

        // You can continue adding more user records as needed...
    }
}