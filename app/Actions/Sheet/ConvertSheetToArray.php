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

  function setColumnMapping(array $columnKeyMapping)
  {
    $this->columnKeyMapping = $columnKeyMapping;
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

  function getSheetData()
  {
    return $this->sheetData;
  }

  function getRowData($row = 1)
  {
    $rowData = [];
    $maxColumns = $this->sheetData->getHighestDataColumn($row);
    $columns = range('A', $maxColumns);
    foreach ($columns as $key => $column) {
      $rowData[$column] = $this->sheetData->getCell($column . $row)->getValue();
    }
    return $rowData;
  }

  function run($startingRow = 1)
  {
    $totalRows = $this->sheetData->getHighestDataRow('A');

    $data = [];
    $rows = range($startingRow + 1, $totalRows);
    foreach ($rows as $row) {
      $dataItem = [];
      $emptyRow = true;
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
        if ($emptyRow) {
          $emptyRow = $value == null || $value == '';
        }
      }
      if (!$emptyRow) {
        $data[] = $dataItem;
      }
    }
    // dd(json_encode($data, JSON_PRETTY_PRINT));
    return $data;
  }
}
