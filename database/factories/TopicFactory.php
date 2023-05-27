<?php

use Faker\Generator as Faker;
use App\Models\Topic;

$factory->define(Topic::class, function (Faker $faker) {
    
    $couseIDs = \App\Models\Course::all('id')->pluck('id')->toArray();
    
    return [
        'course_id' => $faker->randomElement($couseIDs), 
        'title' => $faker->words(8, true), 
        'description' => $faker->paragraph
    ];
});
