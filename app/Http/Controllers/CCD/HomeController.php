<?php
namespace App\Http\Controllers\CCD;

class HomeController extends BaseCCD
{
  function uploadImage($institutionId, $courseId, $courseSessionId)
  {
    /*******************************************************
     * Only these origins will be allowed to upload images *
     ******************************************************/
    $accepted_origins = ['http://localhost', 'http://example.com'];

    reset($_FILES);
    $temp = current($_FILES);

    if (!is_uploaded_file($temp['tmp_name'])) {
      header('HTTP/1.1 500 Server Error');
    }

    if (isset($_SERVER['HTTP_ORIGIN'])) {
      // same-origin requests won't set an origin. If the origin is set, it must be valid.
      if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
      } else {
        //header("HTTP/1.1 403 Origin Denied");

        //return;
      }
    }

    // Sanitize input
    if (preg_match('/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/', $temp['name'])) {
      header('HTTP/1.1 400 Invalid file name.');

      return;
    }

    // Verify extension
    if (
      !in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), [
        'gif',
        'jpg',
        'png'
      ])
    ) {
      //             header("HTTP/1.1 400 Invalid extension.");
      // Accept any origin for now
      //             return;
    }

    //         $imageFolder =  "../public/img/questions/";
    $imageFolder = '../public' . IMG_OUTPUT_PUBLIC_PATH;

    $imageFolder .= "$courseId/$courseSessionId/";

    if (!file_exists($imageFolder)) {
      mkdir($imageFolder, 0777, true);
    }

    $filename = uniqid() . '_' . $temp['name'];

    // Accept upload if there was no origin, or if it is an accepted origin
    $filetowrite = $imageFolder . $filename;

    move_uploaded_file($temp['tmp_name'], $filetowrite);

    //         $filenameFromAssets = "img/questions/$courseCode/$yearId/" . $filename;
    $filenameFromAssets = "img/content/$courseId/$courseSessionId/$filename";

    // Respond to the successful upload with JSON.
    // Use a location key to specify the path to the saved image resource.
    // { location : '/your/uploaded/image/file'}
    //         die(json_encode(array('location' => assets($filenameFromAssets))));

    return response()->json([
      'location' => assets($filenameFromAssets)
    ]);
  }
}
