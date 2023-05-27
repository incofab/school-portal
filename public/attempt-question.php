<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, origin");
header("Content-Type: application/json; charset=UTF-8");

define('APP_DIR', __DIR__ . '/../app/');

require APP_DIR . 'Helpers/ExamHandler.php';
$examHandler = new \App\Helpers\ExamHandler();

$input = @file_get_contents("php://input");
$post = json_decode($input, true);

//     dlog_22($post);
$allAttempts = $post['attempts'];
//     dlog_22($allAttempts);

$ret = $examHandler->attemptQuestion($allAttempts, $post['event_id'], $post['exam_no']);

//     dlog_22($ret);

if($ret['success'] !== true) emitResponse($ret);

emitResponse([
    'success' => true,
    'data' => ['success' => array_values($allAttempts), 'failure' => []]
]);


function emitResponse($data) 
{
    die(json_encode($data));
}


function dlog_22($msg) {
    $str = '';
    
    if (is_array($msg)) $str = json_encode($msg, JSON_PRETTY_PRINT);
    
    else $str = $msg;
    
    error_log(
        '*************************************' . PHP_EOL .
        '     Date Time: ' . date('Y-m-d h:m:s') . PHP_EOL .
        '------------------------------------' . PHP_EOL .
        $str . PHP_EOL . PHP_EOL .
        '*************************************' . PHP_EOL,
        
        3, APP_DIR . '../public/errorlog.txt');
    
}