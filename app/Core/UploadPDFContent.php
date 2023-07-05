<?php
namespace App\Core;

class UploadPDFContent
{
  const FILE_PATH = 'public/pdf-content/';
  const BASE_PATH = '../' . self::FILE_PATH;
  const EXT = 'pdf';

  private $settingsData = [];

  function __construct()
  {
  }

  function uploadContent(
    $files,
    $propertyName,
    $examName,
    $courseCode,
    $session
  ) {
    $localBasePath = self::BASE_PATH . "$examName/$courseCode/";

    if (!file_exists($localBasePath)) {
      mkdir($localBasePath, 0777, true);
    }

    $ret = $this->uploadFile($files, $propertyName, $localBasePath);

    if (!$ret[SUCCESSFUL]) {
      return $ret;
    }

    $fileAddressPath =
      self::FILE_PATH . "$examName/$courseCode/{$ret['filename']}";

    return [
      SUCCESSFUL => true,
      MESSAGE => 'File uploaded',
      FILE_PATH => $fileAddressPath
    ];
  }

  public function downloadContent($filePath)
  {
    $fileToDownload = realpath('../' . $filePath);

    if (!file_exists($fileToDownload)) {
      return [SUCCESSFUL => false, MESSAGE => 'File not found'];
    }

    $file_name = basename($fileToDownload);

    header('Content-type:application/pdf');

    // It will be called downloaded.pdf
    header("Content-Disposition:attachment;filename=$file_name");

    header('Content-Length: ' . filesize($fileToDownload));

    readfile($fileToDownload);

    return [SUCCESSFUL => true, MESSAGE => 'File downloading...'];
  }

  private function uploadFile(
    $files,
    $propertyName,
    $baseFolder,
    $filename = null,
    $maxSizeKilobytes = 500,
    $ext = null
  ) {
    //         echo "propertyName = $propertyName <br />";
    //         dDie($files);
    if (!isset($files[$propertyName])) {
      return [SUCCESSFUL => false, MESSAGE => 'Invalid File'];
    }

    // First check if file type is image
    $fileType = $files[$propertyName]['type'];
    $maxFilesize = $maxSizeKilobytes * 1000; // 500kb
    //         $validFileTypes = ['image/png', 'image/jpg', 'image/jpeg'];

    $name = $files[$propertyName]['name'];

    $ext = $ext ? $ext : pathinfo($name, PATHINFO_EXTENSION);
    $filename =
      ($filename
        ? $filename
        : pathinfo($name, PATHINFO_FILENAME) . '-' . uniqid()) . ".$ext";
    //         if(!in_array($fileType, $validFileTypes)) return [SUCCESSFUL => false, MESSAGE => 'Invalid file type'];

    if ($files[$propertyName]['size'] > $maxFilesize) {
      return [
        SUCCESSFUL => false,
        MESSAGE => "File greater than {$maxSizeKilobytes}kb"
      ];
    }

    // Check if the file contains errors
    if ($files[$propertyName]['error'] > 0) {
      return [
        SUCCESSFUL => false,
        MESSAGE => 'Return Code: ' . $files[$propertyName]['error']
      ];
    }

    $destinationPath = "$baseFolder$filename";

    $tempPath = $files[$propertyName]['tmp_name'];

    try {
      $success = move_uploaded_file($tempPath, $destinationPath); // Moving Uploaded file

      if (!$success) {
        return [SUCCESSFUL => false, MESSAGE => 'Upload failed: Unknown error'];
      }
    } catch (\Exception $e) {
      dlog('PDF uploadFile Error: ' . $e->getMessage());

      return [
        SUCCESSFUL => false,
        MESSAGE => 'Upload failed: ' . $e->getMessage()
      ];
    }

    return [
      SUCCESSFUL => true,
      MESSAGE => 'File uploaded successfully',
      'filename' => $filename
    ];
  }
}
