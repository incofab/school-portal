<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, origin');
header('Content-Type: application/json; charset=UTF-8');

require __DIR__ . '/../app/Helpers/ExamAttemptFileHandler.php';

$input = @file_get_contents('php://input');
$post = json_decode($input, true);

//     dlog_22($post);
$allAttempts = $post['attempts'] ?? [];
$eventId = $post['event_id'] ?? null;
$examNo = $post['exam_no'] ?? null;
// dlog_22($post);

if (empty($allAttempts) || !$eventId || !$examNo) {
  dlog_22('Invalid data ' . json_encode($post, JSON_PRETTY_PRINT));
  return;
}

$examHandler = new \App\Helpers\ExamAttemptFileHandler([
  'event_id' => $eventId,
  'exam_no' => $examNo
]);
$ret = $examHandler->attemptQuestion($allAttempts);

//     dlog_22($ret);
if ($ret['success'] !== true) {
  emitResponse($ret);
}

emitResponse([
  'success' => true,
  'data' => ['success' => array_values($allAttempts), 'failure' => []]
]);

function emitResponse($data)
{
  die(json_encode($data));
}

function dlog_22($msg)
{
  $str = '';

  if (is_array($msg)) {
    $str = json_encode($msg, JSON_PRETTY_PRINT);
  } else {
    $str = $msg;
  }

  error_log(
    '*************************************' .
      PHP_EOL .
      '     Date Time: ' .
      date('Y-m-d h:m:s') .
      PHP_EOL .
      '------------------------------------' .
      PHP_EOL .
      $str .
      PHP_EOL .
      PHP_EOL .
      '*************************************' .
      PHP_EOL,

    3,
    '../public/errorlog.txt'
  );
}
