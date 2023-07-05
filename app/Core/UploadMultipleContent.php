<?php
namespace App\Core;

class UploadMultipleContent
{
  private $bonusContentPath = '../public/bonus-content.zip';
  private $bonusContentPath2 = '../public/bonus-content-2.zip';

  private $settingsFile = '../public/settings.json';

  const IS_INSTALLED_SUFFIX = '.installed.zip';

  const INSTALLED_BONUS_CONTENT_KEY = 'installed_bonus_contents';

  private $extractionFolder = '../public/files/content/extracted';
  private $bonusExtractionFoldername = 'bonus-content';

  private $settingsData = [];

  function __construct()
  {
    if (!file_exists($this->settingsFile)) {
      $arr = [];

      $arr[self::INSTALLED_BONUS_CONTENT_KEY] = [];

      file_put_contents(
        $this->settingsFile,
        json_encode($arr, JSON_PRETTY_PRINT)
      );
    }

    $this->settingsData = json_decode(
      file_get_contents($this->settingsFile),
      true
    );
  }

  function isBonusContentInstalled()
  {
    return in_array(
      $this->bonusContentPath,
      $this->settingsData[self::INSTALLED_BONUS_CONTENT_KEY]
    );
  }

  function isBonusContent2Installed()
  {
    return in_array(
      $this->bonusContentPath2,
      $this->settingsData[self::INSTALLED_BONUS_CONTENT_KEY]
    );
  }

  function installBonusContent()
  {
    return $this->startInstallation($this->bonusContentPath);
  }

  function installBonusContent2()
  {
    return $this->startInstallation($this->bonusContentPath2);
  }

  private function startInstallation($contentPath)
  {
    if (file_exists($contentPath . self::IS_INSTALLED_SUFFIX)) {
      return [SUCCESSFUL => false, MESSAGE => 'Content already installed'];
    }

    if (!file_exists($contentPath)) {
      return [SUCCESSFUL => false, MESSAGE => 'Error: File not found'];
    }

    ini_set('max_execution_time', 1440);

    $ret = $this->extractAndFormatContent(
      $contentPath,
      $this->bonusExtractionFoldername
    );

    // Update settings
    if (
      !in_array(
        $contentPath,
        $this->settingsData[self::INSTALLED_BONUS_CONTENT_KEY]
      )
    ) {
      $this->settingsData[self::INSTALLED_BONUS_CONTENT_KEY][] = $contentPath;

      file_put_contents(
        $this->settingsFile,
        json_encode($this->settingsData, JSON_PRETTY_PRINT)
      );
    }

    return $ret;
  }

  function extractAndFormatContent($contentPath, $extractionFolderName = null)
  {
    $zip = new \ZipArchive();
    $res = $zip->open($contentPath);
    $folderName = empty($extractionFolderName)
      ? pathinfo($contentPath, PATHINFO_FILENAME)
      : $extractionFolderName;

    if ($res !== true) {
      die('<h2>File could not open</h2>');
    }

    $contentDir = "{$this->extractionFolder}/$folderName";

    $zip->extractTo($contentDir);
    $zip->close();

    $bigArr = [];
    $coursesList = scandir($contentDir);

    foreach ($coursesList as $courseDir) {
      if (in_array($courseDir, ['.', '..'])) {
        continue;
      }

      $fullPath = $contentDir . '/' . $courseDir;

      if (!is_dir($fullPath)) {
        continue;
      }

      $formatedSessionsAndQuestions = $this->formatSessionsAndQuestions(
        $fullPath
      );

      $last = last($formatedSessionsAndQuestions);

      if (empty($last)) {
        continue;
      }

      $course = [
        COURSE_CODE => $last['session_data'][COURSE_CODE],
        COURSE_TITLE => $courseDir,
        DESCRIPTION => ''
      ];

      $courseContent = [
        'course' => $course,
        'session_and_questions' => $formatedSessionsAndQuestions
      ];

      $this->insertCourse($courseContent);
    }

    return [SUCCESSFUL => true, MESSAGE => 'Data recorded'];
  }

  private function formatSessionsAndQuestions($courseDir)
  {
    $allSessions = [];

    $courseTitle = pathinfo($courseDir, PATHINFO_FILENAME);

    $sessionsList = scandir($courseDir);

    foreach ($sessionsList as $file) {
      if (in_array($file, ['.', '..'])) {
        continue;
      }

      $subjectDir = $courseDir . '/' . $file;

      if (!is_dir($subjectDir)) {
        continue;
      }

      $arr = str_getcsv($file, '_');

      if (empty($arr[0]) || empty($arr[1])) {
        continue;
      }

      $courseCode = $arr[0];
      $year = $arr[1];

      $courseData = [
        COURSE_CODE => $courseCode,
        COURSE_TITLE => $courseTitle,
        DESCRIPTION => ''
      ];

      $obj = new \App\Parser\GenericParse($year, $subjectDir);

      $ret = $obj->parse($courseData);

      if (!$ret[SUCCESSFUL]) {
        dlog($ret[SUCCESSFUL]);

        continue;
      }

      $allSessions[] = $ret;
    }

    return $allSessions;
  }

  private function multiInsertContent($allRecords)
  {
    foreach ($allRecords as $record) {
      $this->insertCourse($record);
    }

    return [SUCCESSFUL => true, MESSAGE => 'Bonus Content installed'];
  }

  private function insertCourse($formattedCourseContent)
  {
    $courseData = $formattedCourseContent['course'];

    $courseSessionsAndQuestions =
      $formattedCourseContent['session_and_questions'];

    $courseObj = new \App\Models\Course();
    if (
      !$courseObj->where(COURSE_CODE, '=', $courseData[COURSE_CODE])->first()
    ) {
      $courseObj->insert($courseData);
    }

    foreach ($courseSessionsAndQuestions as $sessionAndQuestions) {
      $this->insertSessionAndQuestionsRecord(
        $sessionAndQuestions['session_data'],
        $sessionAndQuestions['questions']
      );
    }
  }

  private function insertSessionAndQuestionsRecord(
    $sessionData,
    $formattedQuestions
  ) {
    $session = new \App\Models\CourseSession();

    $session = $session->insert($sessionData);

    if (empty($session[SESSION])) {
      return [SUCCESSFUL => false, MESSAGE => 'Failed to record Acad Session'];
    }

    foreach ($formattedQuestions as $questionsArr) {
      $questionsArr[SESSION_ID] = $session[TABLE_ID];

      $questionRet = (new \App\Models\Question())->insert($questionsArr);

      if (empty($questionRet[SUCCESSFUL])) {
        dlog(array_merge($questionsArr, $questionRet));

        dDie($questionRet);
      }
    }

    return [SUCCESSFUL => true, MESSAGE => 'Data recorded'];
  }
}
