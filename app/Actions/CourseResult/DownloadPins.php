<?php
namespace App\Actions\CourseResult;

use App\Models\Pin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadPins
{
  private const COL_SERIAL = 'A';
  private const COL_PIN = 'B';
  private Worksheet $workSheet;
  private Spreadsheet $spreadsheet;

  /** @param Collection<string, Pin> $pins */
  function __construct(private Collection $pins)
  {
    $this->spreadsheet = new Spreadsheet();
    $this->workSheet = $this->spreadsheet->getActiveSheet();
  }

  /** @param Collection<string, Pin> $pins */
  public static function run(Collection $pins): Xlsx
  {
    $obj = new self($pins);
    return $obj->execute();
  }

  public function execute(): Xlsx
  {
    $startingRow = 1;
    $row = $startingRow;
    $this->setHeaders($row);
    $row++;

    foreach ($this->pins as $key => $pin) {
      $this->insert($pin, $row);
      $row++;
    }

    return new Xlsx($this->spreadsheet);
  }

  private function setHeaders(int $row)
  {
    $this->workSheet
      ->getStyle($row)
      ->getFont()
      ->setBold(true);
    $this->workSheet
      ->getStyle(self::COL_PIN)
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $this->workSheet
      ->setCellValue(self::COL_SERIAL . $row, 'Serial')
      ->getColumnDimension(self::COL_SERIAL)
      ->setWidth(20);
    $this->workSheet
      ->setCellValue(self::COL_PIN . $row, 'Pin')
      ->getColumnDimension(self::COL_PIN)
      ->setWidth(25);
  }

  public function insert(Pin $pin, int $row)
  {
    $this->workSheet->setCellValue(
      self::COL_SERIAL . $row,
      date('mY') . Str::padLeft($pin->id, 8, '0')
    );

    $this->workSheet->setCellValue(self::COL_PIN . $row, $pin->pin);
  }
}
