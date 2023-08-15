<?php
namespace App\Core;

class Config
{
  static function examBodyImgs()
  {
    return [
      'JAMB/UTME' => asset('img/exam-body/jamb.png'),
      'UTME' => asset('img/exam-body/jamb.png'),
      'JAMB Literature' => asset('img/exam-body/jamb.png'),

      'WAEC/SSCE' => asset('img/exam-body/waec.png'),
      'WAEC' => asset('img/exam-body/waec.png'),

      //NECO
      'NECO' => asset('img/exam-body/neco.jpg'),
      'NECO/NCEE' => asset('img/exam-body/neco.jpg'),
      'BECE' => asset('img/exam-body/neco.jpg')
    ];
  }
}
