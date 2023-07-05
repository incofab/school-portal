<?php
namespace App\Http\Controllers\CCD;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseUploadController extends BaseCCD
{
  private $sessionModel;
  private $multiContentUploader;
  private $courseInstaller;
  private $exportContent;

  function __construct(
    \App\Core\CourseInstaller $courseInstaller,
    \App\Core\ExportContent $exportContent
  ) {
    $this->courseInstaller = $courseInstaller;
    $this->exportContent = $exportContent;
  }

  function uploadCourseView($institutionId, $courseId)
  {
    $course = \App\Models\Course::whereId($courseId)->firstOrFail();

    return $this->view('ccd.course.upload', [
      'course' => $course
    ]);
  }

  function uploadCourse($institutionId, $courseId, Request $request)
  {
    $filename = "{$this->courseInstaller->coursesFolder}$courseId.zip";

    if (file_exists($filename)) {
      unlink($filename);
    }

    $ret = $this->uploadFile($_FILES, $filename);

    if (!$ret[SUCCESSFUL]) {
      return $this->redirect(redirect()->back(), $ret);
    }

    $ret = $this->courseInstaller->installCourse($courseId);

    if (!$ret[SUCCESSFUL]) {
      return $this->redirect(redirect()->back(), $ret);
    }

    return redirect(
      route('ccd.session.index', [$institutionId, $courseId])
    )->with('message', $ret[MESSAGE]);
  }

  function unInstallCourse($institutionId, $courseId)
  {
    $ret = $this->courseInstaller->unInstallCourse($courseId);

    if (!$ret[SUCCESSFUL]) {
      return $this->redirect(redirect()->back(), $ret);
    }

    return redirect(
      route('ccd.course.index', [$institutionId, $courseId])
    )->with('message', $ret[MESSAGE]);
  }

  function exportCourse($institutionId, $courseId)
  {
    $course = Course::whereId($courseId)->firstOrFail();

    $this->exportContent->exportCourse($course);

    die('Done');
  }

  private function uploadFile($files, $destinationPath = null)
  {
    if (!isset($files['content'])) {
      return [SUCCESSFUL => false, MESSAGE => 'Invalid File'];
    }

    // First check if file type is image
    $validExtensions = ['zip'];
    $maxFilesize = 50000000; // 50mb

    $name = $files['content']['name'];

    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $originalFilename = pathinfo($name, PATHINFO_FILENAME);

    if ($files['content']['size'] > $maxFilesize) {
      return [SUCCESSFUL => false, MESSAGE => 'File greater than 50mb'];
    }

    if (!in_array($ext, $validExtensions)) {
      return [SUCCESSFUL => false, MESSAGE => 'Invalid file Extension'];
    }

    // Check if the file contains errors
    if ($files['content']['error'] > 0) {
      return [
        SUCCESSFUL => false,
        MESSAGE =>
          'File upload error. Return Code: ' . $files['content']['error']
      ];
    }

    $filename = $destinationPath;

    // Now upload the file
    if (!$destinationPath) {
      $originalFilename = pathinfo($name, PATHINFO_FILENAME);
      $filename = "$originalFilename" . '_' . uniqid() . ".$ext";
      $destinationFolder = '../public/files/content/';
      if (!file_exists($destinationFolder)) {
        mkdir($destinationFolder, 0777, true);
      }
      $destinationPath = $destinationFolder . $filename;
    }

    $tempPath = $files['content']['tmp_name'];

    $move = move_uploaded_file($tempPath, $destinationPath); // Moving Uploaded file

    return [
      SUCCESSFUL => true,
      MESSAGE => 'File uploaded successfully',
      'full_path' => $destinationPath
    ];
  }
}
