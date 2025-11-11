<?php
namespace App\Actions;

use App\Models\Classification;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClassSheet
{
  private const COL_TITLE = 'A';
  private const COL_HAS_EQUAL_SUBJECTS = 'B';

  function __construct(private Institution $institution)
  {
  }

  public static function make(Institution $institution)
  {
    return new self($institution);
  }

  /** @param Collection<string, Classification> $classifications */
  public function download(Collection $classifications): Xlsx
  {
    $spreadsheet = new Spreadsheet();
    $workSheet = $spreadsheet->getActiveSheet();

    $startingRow = 1;
    $row = $startingRow;
    $this->setHeaders($workSheet, $row);
    $row++;

    foreach ($classifications as $key => $classification) {
      $this->insert($workSheet, $classification, $row);
      $row++;
    }

    return new Xlsx($spreadsheet);
  }

  private function setHeaders(Worksheet $workSheet, int $row)
  {
    $workSheet
      ->getStyle($row)
      ->getFont()
      ->setBold(true);
    $workSheet
      ->getStyle(self::COL_HAS_EQUAL_SUBJECTS)
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $workSheet
      ->setCellValue(self::COL_TITLE . $row, 'Name')
      ->getColumnDimension(self::COL_TITLE)
      ->setWidth(25);
    $workSheet
      ->setCellValue(
        self::COL_HAS_EQUAL_SUBJECTS . $row,
        'Equal Subjects (Y/N)'
      )
      ->getColumnDimension(self::COL_HAS_EQUAL_SUBJECTS)
      ->setWidth(25);
  }

  private function insert(
    Worksheet $workSheet,
    Classification $classification,
    int $row
  ) {
    $workSheet
      ->setCellValue(self::COL_TITLE . $row, $classification->title)
      ->setCellValue(
        self::COL_HAS_EQUAL_SUBJECTS . $row,
        $classification->has_equal_subjects ? 'Y' : 'N'
      );
  }

  /** @deprecated No longer in use */
  public function upload(UploadedFile $file)
  {
    $sheetData = IOFactory::load($file->getRealPath())->getActiveSheet();

    $totalRows = $sheetData->getHighestDataRow(self::COL_TITLE);
    $rows = range(2, $totalRows);

    foreach ($rows as $row) {
      $hasEqualSubjects =
        strtolower(
          $sheetData->getCell(self::COL_HAS_EQUAL_SUBJECTS . $row)->getValue()
        ) === 'y'
          ? true
          : false;
      Classification::query()->updateOrCreate(
        [
          'institution_id' => $this->institution->id,
          'title' => $sheetData->getCell(self::COL_TITLE . $row)->getValue()
        ],
        ['has_equal_subjects' => $hasEqualSubjects]
      );
    }
  }
}
