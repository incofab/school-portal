<?php

/** Master@365
 * The directory to the app files
 */
define('APP_DIR', __DIR__ . '/../app/');

$originalUrl = substr(
  $_SERVER['REQUEST_URI'],
  0,
  stripos($_SERVER['REQUEST_URI'], '&filename')
);
$urlParts = parse_url($originalUrl)['query'];
parse_str($urlParts, $urlQueryParts);

/** $courseId here is the Course code */
$courseId = $urlQueryParts['course_id']; //$_REQUEST['course_id'];
$course_session_id = $urlQueryParts['course_session_id']; //$_REQUEST['course_session_id'];
$year = $_REQUEST['session'];

$filename = parseFilename($_REQUEST['filename']);
$slashPos = strrpos($filename, '/');
$filename = trim(substr($filename, $slashPos), '/');
// dlog_22('Filename = '.$filename);
/*
// dlog_22("Filename 1 = $filename");
if(stripos($filename, '?')){
    $filename = substr($filename, 0, stripos($filename, '?'));
}

// dlog_22("Filename 2 = $filename");
if($slashPositon = strripos($filename, '/')){
    $filename = substr($filename, $slashPositon+1);
}
// dlog_22("Filename 3 = $filename");
// $filename = pathinfo($filename, PATHINFO_FILENAME);
*/

$file = "../public/img/content/$courseId/$course_session_id/$filename";
$file2 = "../public/img/content/$courseId/$year/$filename";

// dlog_22("File = $file");
// dlog_22("File 2 = $file2");

if (!file_exists($file)) {
  $file = $file2;
}
if (!file_exists($file)) {
  return null;
}

$type = 'image/jpeg';
header('Content-Type:' . $type);
header('Content-Length: ' . filesize($file));
readfile($file);

function parseFilename($filename)
{
  $urlparts = parse_url($filename); //['path'];//getUrlPath();

  if (empty($urlparts['path'])) {
    return $filename;
  }
  //         dDie($urlparts);
  if (empty($urlparts['query'])) {
    return $urlparts['path'];
  }

  parse_str($urlparts['query'], $urlparts2);

  return parseFilename($urlparts2['filename']);
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
