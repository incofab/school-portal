<?php
namespace App\Actions;

use App\Enums\S3Folder;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;
use DB;
use File;
use Illuminate\Http\UploadedFile;

class UploadCourseContent
{
  function __construct(
    private Course $course,
    private UploadedFile $uploadedFile
  ) {
  }

  static function run(Course $course, UploadedFile $uploadedFile)
  {
    return (new self($course, $uploadedFile))->execute();
  }

  function execute()
  {
    ini_set('max_execution_time', 20 * 60);
    $baseFolder = self::unzipFile($this->uploadedFile);
    DB::beginTransaction();
    $this->insertSessions($baseFolder, $this->course);
    DB::commit();
  }

  private function insertSessions(string $baseFolder, Course $course)
  {
    $sessionsFile = "$baseFolder/sessions.json";
    abort_unless(file_exists($sessionsFile), 401, 'Session file not found');
    $sessionsArr = json_decode(file_get_contents($sessionsFile), true);

    foreach ($sessionsArr as $key => $sessionData) {
      // if ($sessionData['session'] != '2000') {
      //   continue;
      // }
      $createdCourseSession = $course->sessions()->firstOrCreate(
        [
          'session' => $sessionData['session'],
          'institution_id' => $course->institution_id
        ],
        collect($sessionData)
          ->only([
            'category',
            'general_instructions',
            'file_path',
            'file_version'
          ])
          ->toArray()
      );
      Passage::multiInsert(
        $createdCourseSession,
        $sessionData['passages'] ?? []
      );
      Instruction::multiInsert(
        $createdCourseSession,
        $sessionData['instructions'] ?? []
      );
      self::insertQuestions($baseFolder, $sessionData, $createdCourseSession);
      self::transferImages(
        "$baseFolder/img",
        $sessionData,
        $createdCourseSession
      );
    }
    File::deleteDirectory($baseFolder);
  }

  private static function insertQuestions(
    string $baseFolder,
    $sourceCourseSession,
    CourseSession $courseSession
  ) {
    $questionsFile = "$baseFolder/questions_{$sourceCourseSession['session']}_{$sourceCourseSession['id']}.json";

    if (!file_exists($questionsFile)) {
      return;
    }

    $questionsArr = json_decode(file_get_contents($questionsFile), true);

    Question::multiInsert($courseSession, $questionsArr);
  }

  private static function transferImages(
    $baseFolder,
    array $sourceCourseSession,
    CourseSession $destinationCourseSession
  ) {
    $sessionImagesFolder = "$baseFolder/{$sourceCourseSession['id']}";
    if (!file_exists($sessionImagesFolder)) {
      return;
    }
    $destinationFolder = $destinationCourseSession->institution->folder(
      S3Folder::CCD,
      "{$destinationCourseSession->course_id}/{$destinationCourseSession->id}"
    );

    AwsFileHelper::uploadDirectory($sessionImagesFolder, $destinationFolder);
  }

  static function unzipFile(UploadedFile $uploadedFile)
  {
    $zipPath = $uploadedFile->store('temp'); // Store the uploaded zip file in the 'temp' folder
    $zipFilePath = storage_path("app/$zipPath");

    $folderName =
      pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME) .
      uniqid('-');
    // Unzip the file
    $unzipPath = storage_path("app/unzipped/$folderName");
    $zip = new \ZipArchive();
    $zip->open($zipFilePath);
    $zip->extractTo($unzipPath);
    $zip->close();

    // Clean up: Delete the uploaded zip file
    File::delete($zipFilePath);
    return $unzipPath;
  }
}
