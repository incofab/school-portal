<?php

$reactFolder = 'build'; //DEV ? 'public' : 'build';

$htmlFilename = "../public/examiner/$reactFolder/index.html";

$dom = new \DOMDocument();
$dom->loadHTMLFile($htmlFilename);

$scriptNode = $dom->createElement('script');
$scriptNode->appendChild(
  new \DOMText(
    'window.dev = ' .
      json_encode(DEV) .
      '; ' .
      'window.event_id = ' .
      json_encode($eventId) .
      '; ' .
      'window.mainContent = ' .
      json_encode($examData) .
      '; '
  )
);

//     $body = $dom->getElementsByTagName('body')->item(0);
$body = $dom->getElementById('startup-record');
$body->appendChild($scriptNode);
$finalHTML = $dom->saveHTML();

//     dlog($final);
//     dDie($final);
//     $finalHTML = file_get_contents($htmlFilename);

echo $finalHTML;
