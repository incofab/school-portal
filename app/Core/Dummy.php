<?php
namespace App\Core;

use App\Models\ExamContent;
use App\Models\SubscriptionPlan;

class Dummy
{
    function __construct()
    {
        
    }
    
    static function seedSubscriptionPlans()
    {
        $examContent = ExamContent::where('exam_name', '=', 'UTME')->first();
        
        if(!$examContent) $examContent = ExamContent::where('exam_name', '=', 'JAMB/UTME')->first();
        
        if(!$examContent) return ret(false, 'UTME exam content not found');
        
        $arr = [
            [ 'duration' => 7, 'price' => 150 ],
            [ 'duration' => 14, 'price' => 250 ],
            [ 'duration' => 30, 'price' => 300 ],
            [ 'duration' => 60, 'price' => 500 ],
            [ 'duration' => 90, 'price' => 900 ],
//             [ 'duration' => 120, 'price' => 1000 ],
//             [ 'duration' => 180, 'price' => 2400 ],
            [ 'duration' => 365, 'price' => 1000 ],
        ];
        
        foreach ($arr as $post) 
        {
            $ret = SubscriptionPlan::insert($examContent, $post);
        }
        
        return ret(true, 'Subscriptions seeded');
    }

    
}