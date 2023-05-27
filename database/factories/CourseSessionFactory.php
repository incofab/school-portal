<?php

use Faker\Generator as Faker;
use App\Models\CourseSession;

$factory->define(CourseSession::class, function (Faker $faker) {
    
    $couseIDs = \App\Models\Course::all('id')->pluck('id')->toArray();
    $sessions = ['2001', '2002', '2003', '2004', '2005', '2006'];
    
    return [
        'course_id' => $faker->randomElement($couseIDs), 
        'category' => '', 
        'session' => $faker->randomElement($sessions)
    ];
});
