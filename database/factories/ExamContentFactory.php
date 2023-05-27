<?php

use Faker\Generator as Faker;
use App\Models\ExamContent;

$factory->define(ExamContent::class, function (Faker $faker) {
    
    $examname = ['WAEC', 'JAMB/UTME', 'NECO'];
    
    $examFullname = ['West African Examination Council', 'Universal Tertiary Matriculation Examination', 
    'National Examination Council'];
    
    $index = $faker->unique(false, 50)->randomKey($examname);
    
    return [
        'country' => $faker->country,  
        'exam_name' => $examname[$index], 
        'fullname' => $examFullname[$index], 
        'is_file_content_uploaded' => false,
        'description' => $faker->sentence,
    ];
    
});
