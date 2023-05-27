<?php

use App\Models\User;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Models\SubscriptionPlan;
use App\Models\ExamContent;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(SubscriptionPlan::class, function (Faker $faker) {
    
    $durations = [7, 14,30,60,90,120,180,365];
    $prices = [70, 140,300,600,900,1200,1800,3650];
    $examContentIDs = ExamContent::all('id')->pluck('id')->toArray();
    
    $index = $faker->randomKey($durations);
    
    return [
        'exam_content_id' => $faker->randomElement($examContentIDs), 
        'duration' => $durations[$index], 
        'price' => $prices[$index],
    ];
});
