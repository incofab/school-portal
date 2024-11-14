<?php
namespace App\Actions\Sheet;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConvertSheetToArray
{
  private Spreadsheet $spreadsheet;
  private Worksheet $sheetData;
  /**
   * @param array<string, string> $columnKeyMapping
   */
  function __construct(
    private UploadedFile $file,
    private array $columnKeyMapping
  ) {
    $this->spreadsheet = IOFactory::load($this->file->getRealPath());
    $this->sheetData = $this->spreadsheet->getActiveSheet();
  }

  // Example
  // private $columnKeyMapping = [
  //   'A' => 'question' | SheetValueHandler,
  //   'B' => 'option_a',
  //   'C' => 'option_b',
  //   'D' => 'option_c',
  //   'E' => 'option_d',
  //   'F' => 'answer',
  // ];

  function run($startingRow = 1)
  {
    $totalRows = $this->sheetData->getHighestDataRow('A');

    $data = [];
    $rows = range($startingRow + 1, $totalRows);
    foreach ($rows as $row) {
      $dataItem = [];
      foreach ($this->columnKeyMapping as $excelColumn => $dataKey) {
        $key = $dataKey;
        $value = trim(
          $this->sheetData->getCell($excelColumn . $row)->getValue()
        );
        if ($dataKey instanceof SheetValueHandler) {
          $key = $dataKey->key;
          $value = $dataKey->handleValue($value);
        }
        $dataItem[$key] = $value;
      }
      $data[] = $dataItem;
    }
    // dd(json_encode($data, JSON_PRETTY_PRINT));
    return $data;
  }
}
