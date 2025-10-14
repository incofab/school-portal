<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClassDivision;

class ClassDivisionSeeder extends Seeder
{
    public function run()
    {
      ClassDivision::create(['institution_id' => 1, 'title' => 'Science']);
      ClassDivision::create(['institution_id' => 1, 'title' => 'Art']);
      ClassDivision::create(['institution_id' => 1, 'title' => 'Commercial']);

        // You can continue adding more user records as needed...
    }
}