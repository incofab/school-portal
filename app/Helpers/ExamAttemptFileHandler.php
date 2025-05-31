<?php
namespace App\Helpers;

class ExamAttemptFileHandler
{
  const EXAM_TIME_ALLOWANCE = 100; // 100 seconds

  function __construct(private array|object $exam)
  {
  }

  static function make(array|object $exam)
  {
    return new self($exam);
  }

  /**
   * This creates an exam file if it doesn't exits or updates it
   * @param \App\Models\Exam $exam
   * @return boolean[]|string[]
   */
  function syncExamFile()
  {
    $file = $this->getFullFilepath(true);

    $examFileContent = file_exists($file)
      ? json_decode(file_get_contents($file), true)
      : null;

    // If it's not empty, then the exam has just been restarted
    if (empty($examFileContent)) {
      $examFileContent = [
        'exam' => $this->exam,
        'attempts' => []
      ];
    } else {
      $examFileContent['exam'] = $this->exam;
    }

    $ret = $this->save($file, json_encode($examFileContent, JSON_PRETTY_PRINT));

    if ($ret === false) {
      return $this->res(false, 'Exam file failed to create');
    }

    return $this->res(true, 'Exam file ready');
  }

  function getContent($checkTime = true)
  {
    $file = $this->getFullFilepath(false);

    if (!file_exists($file)) {
      return $this->res(false, 'Exam file not found', [
        'exam_not_found' => true
      ]);
    }

    $examFileContent = json_decode(file_get_contents($file), true);

    if (empty($examFileContent)) {
      return $this->res(false, 'Exam file not found', [
        'exam_not_found' => true
      ]);
    }

    /************Check Exam Time**************/
    if ($checkTime) {
      $exam = $examFileContent['exam'];
      $currentTime = time();
      $endTime = strtotime($exam['end_time']) + self::EXAM_TIME_ALLOWANCE;

      if ($currentTime > $endTime) {
        return $this->res(false, 'Time Elapsed', [
          'time_elapsed' => true,
          'content' => $examFileContent
        ]);
      }
    }
    /*//***********Check Exam Time**************/

    return $this->res(true, '', [
      'content' => $examFileContent,
      'file' => $file
    ]);
  }

  function attemptQuestion(array $studentAttempts)
  {
    $ret1 = $this->getContent();

    if ($ret1['success'] !== true) {
      return $ret1;
    }

    $examFileContent = $ret1['content'];
    $file = $ret1['file'];
    $savedAttempts = $examFileContent['attempts'];

    foreach ($studentAttempts as $questionId => $studentAttempt) {
      $savedAttempts[$questionId] = $studentAttempt;
    }

    $examFileContent['attempts'] = $savedAttempts;

    $ret = $this->save($file, json_encode($examFileContent, JSON_PRETTY_PRINT));

    if ($ret === false) {
      return $this->res(false, 'Exam file failed to recorded attempt');
    }

    return $this->res(true, 'Exam file, question attempt recorded');
  }

  function calculateScoreFromFile($questions)
  {
    $size = count($questions);
    $subjectAttempts =
      $this->getQuestionAttempts() ?? ($this->exam['attempts'] ?? []);

    if (empty($subjectAttempts)) {
      return ['success' => true, 'score' => 0, 'num_of_questions' => $size];
    }

    $score = 0;
    // $subjectAttempts = $examFileContent['attempts']; //[$examSubjectId];
    foreach ($questions as $question) {
      $attempt = $subjectAttempts[$question['id']] ?? null;
      if ($attempt === $question['answer']) {
        $score++;
      }
    }

    return ['success' => true, 'score' => $score, 'num_of_questions' => $size];
  }

  function deleteExamFile()
  {
    $file = $this->getFullFilepath(false);
    if (!file_exists($file)) {
      return;
    }
    unlink($file);
  }

  function getQuestionAttempts()
  {
    $contentRet = $this->getContent(false);
    return $contentRet['content']['attempts'] ?? [];
  }

  private function res(bool $success, string $message, $data = [])
  {
    return [
      'success' => $success,
      'message' => $message,
      ...$data
    ];
  }

  function getFullFilepath($toCreateBaseFolder = true)
  {
    $ext = 'edr';
    $examNo = $this->exam['exam_no'];
    $eventId = $this->exam['event_id'];

    $filename = "exam_$examNo";
    $examFolderName = "event_$eventId";
    $baseFolder = __DIR__ . "/../../public/exams/$examFolderName";

    if (!file_exists($baseFolder) && $toCreateBaseFolder) {
      mkdir($baseFolder, 0777, true);
    }

    return "$baseFolder/$filename.$ext";
  }

  private function save($filename, $data)
  {
    //file_put_contents($filename, $data);
    // 1. Open the file for writing (or creating if it doesn't exist)
    // 'c+' mode opens the file for read/write, and creates it if it doesn't exist,
    // but does not truncate it like 'w+' would. This is often safer for locking scenarios.
    $fileHandle = fopen($filename, 'c+');

    if ($fileHandle === false) {
      return $this->res(false, 'Error: Could not open file for writing.');
    }

    // 2. Acquire an exclusive lock (LOCK_EX)
    // LOCK_EX means only one process can hold this lock at a time.
    // By default, flock() will block until the lock can be acquired.
    if (flock($fileHandle, LOCK_EX)) {
      // 3. IMPORTANT: Truncate the file if you want to overwrite it
      // Without ftruncate, new data would be appended by default or overwrite existing data
      // up to the length of the new data.
      ftruncate($fileHandle, 0);

      // 4. Move the file pointer to the beginning (important after truncating)
      rewind($fileHandle);

      // 5. Write the data
      fwrite($fileHandle, $data);

      // 6. Flush the output buffer to ensure data is written to disk
      fflush($fileHandle);

      // 7. Release the lock
      flock($fileHandle, LOCK_UN);

      // 8. Close the file handle
      fclose($fileHandle);
      return $this->res(true, 'Data successfully written and lock released.');
    } else {
      // 8. Close the file handle
      fclose($fileHandle);
      return $this->res(
        false,
        'Error: Could not obtain file lock. Another process might be writing.'
      );
    }
  }
}
