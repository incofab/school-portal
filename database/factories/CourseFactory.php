<?php

use Faker\Generator as Faker;
use App\Models\Course;

$factory->define(Course::class, function (Faker $faker) {
    
    $courseCodes = ['Engish', 'Maths', 'Economics', 'Biology'];
    
    $examContentIDs = \App\Models\ExamContent::all('id')->pluck('id')->toArray();
    
    return [
        'course_code' => $faker->randomElement($courseCodes), 
        'exam_content_id' => $faker->randomElement($examContentIDs), 
        'category' => $faker->word, 
        'course_title' => $faker->words(7, true), 
        'description' => $faker->sentence, 
        'is_file_content_uploaded' => false
    ];
    
});
