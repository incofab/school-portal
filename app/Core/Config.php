<?php
namespace App\Core;

class Config
{
    static function examBodyImgs()
    {
        return [
            'JAMB/UTME' => assets('img/exam-body/jamb.png'),
            'UTME' => assets('img/exam-body/jamb.png'),
            'JAMB Literature' => assets('img/exam-body/jamb.png'),
            
            'WAEC/SSCE' => assets('img/exam-body/waec.png'),
            'WAEC' => assets('img/exam-body/waec.png'),
            
            //NECO
            'NECO' => assets('img/exam-body/neco.jpg'),
            'NECO/NCEE' => assets('img/exam-body/neco.jpg'),
            'BECE' => assets('img/exam-body/neco.jpg'),
        ];
    }
    
}








