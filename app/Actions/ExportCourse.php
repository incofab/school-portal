<?php
namespace App\Actions;

use App\Models\Course;

class ExportCourse
{
  private $baseDir;
  private $contentFolder;
  /** Folder where content images are located */
  //   private $contentImagesFolder;

  function __construct(private Course $course)
  {
    $this->baseDir = public_path('export/');
    $this->contentFolder = "{$this->baseDir}{$course->id}";
    // $this->contentImagesFolder = public_path(
    //   config('app.image-content-folder') . "{$course->id}"
    // );
  }

  static function run(Course $course)
  {
    return (new self($course))->execute();
  }

  function execute()
  {
    ini_set('max_execution_time', 480);

    $this->course->load('examContent');
    $sessions = $this->course
      ->sessions()
      ->with('passages', 'instructions')
      ->get();
    $summaries = $this->course->summaries()->get();

    abort_if(empty($sessions), 401, 'No content found');

    if (is_dir($this->contentFolder)) {
      \App\Core\Helper::deleteDir($this->contentFolder, false);
    } else {
      mkdir($this->contentFolder, 0777, true);
    }

    file_put_contents(
      "{$this->contentFolder}/course.json",
      json_encode($this->course->toArray(), JSON_PRETTY_PRINT)
    );

    file_put_contents(
      "{$this->contentFolder}/sessions.json",
      json_encode($sessions->toArray(), JSON_PRETTY_PRINT)
    );

    if ($summaries->first()) {
      file_put_contents(
        "{$this->contentFolder}/summary.json",
        json_encode($summaries->toArray(), JSON_PRETTY_PRINT)
      );
    }

    /** @var \App\Models\Session $session */
    foreach ($sessions as $session) {
      $question = $session
        ->questions()
        ->with('topic')
        ->get();
      file_put_contents(
        "{$this->contentFolder}/questions_{$session->session}_{$session->id}.json",
        json_encode($question->toArray(), JSON_PRETTY_PRINT)
      );
    }

    // Where to copy the images to
    $imgBaseFolder = "{$this->contentFolder}/img";
    foreach ($sessions as $session) {
      AwsFileHelper::downloadFromS3(
        "{$this->course->id}/{$session->id}",
        "$imgBaseFolder/{$session->id}"
      );
    }
    // if(!\App\Core\Helper::is_dir_empty($this->contentImagesFolder))
    // {
    //     mkdir($imgBaseFolder, 0777, true);

    // 	\App\Core\Helper::copy($this->contentImagesFolder, $imgBaseFolder);
    // }

    $currentTime = str_replace(
      [' ', ':'],
      ['_', '-'],
      \Carbon\Carbon::now()->toDateTimeString()
    );

    $zipFilename = "{$this->baseDir}{$this->course->id}-{$this->course->}.$currentTime.zip";

    \App\Core\Helper::zipContent($this->contentFolder, $zipFilename);

    $this->downloadContent($zipFilename);

    // Delete the file afterwards
    unlink($zipFilename);

    if (is_dir($this->contentFolder)) {
      \App\Core\Helper::deleteDir($this->contentFolder);
    }
    exit();
  }

  private function downloadContent($fileToDownload)
  {
    $file_name = basename($fileToDownload);

    header('Content-Type: application/zip');

    header("Content-Disposition: attachment; filename=$file_name");

    header('Content-Length: ' . filesize($fileToDownload));

    readfile($fileToDownload);
  }
}
