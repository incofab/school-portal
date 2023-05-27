<?php
namespace App\Http\Controllers\CCD;

use App\Http\Controllers\Controller;

class BaseCCD extends Controller
{

	static function getPassage($allPassages, $QuestionNo) {
		foreach ($allPassages as $passage) {
			if($QuestionNo >= $passage['from_'] && $QuestionNo <= $passage['to_'])
				return $passage;
		}
		return null;
	}
	
	static function getInstruction($allInstructions, $QuestionNo) {
		foreach ($allInstructions as $instruction) {

			if($QuestionNo >= $instruction['from_'] && $QuestionNo <= $instruction['to_']){

				return $instruction;
			}
		}
		return null;
	}
}