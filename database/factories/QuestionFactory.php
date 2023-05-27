<?php

use Faker\Generator as Faker;
use App\Models\Question;

$factory->define(Question::class, function (Faker $faker) {
    
    $couseSessionIDs = \App\Models\CourseSession::all('id')->pluck('id')->toArray();
    $topicIDs = \App\Models\Topic::all('id')->pluck('id')->toArray();
    
    return [
        'course_session_id' => $faker->randomElement($couseSessionIDs), 
        'topic_id' => $faker->randomElement($topicIDs), 
        'question_no' => rand(1,50), 
        'question' => $faker->paragraph,
        'option_a' => $faker->sentence, 
        'option_b' => $faker->sentence, 
        'option_c' => $faker->sentence, 
        'option_d' => $faker->sentence, 
        'option_e' => $faker->sentence, 
        'answer' => $faker->randomElement(['A', 'B', 'C', 'D']), 
        'answer_meta' => $faker->paragraph
    ];
});
