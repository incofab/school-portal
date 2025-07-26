<?php

namespace App\Actions;

use Exception;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Response;

class GenericExport
{
  private array $columns;
  public function __construct(
    private array|Collection $data,
    private string $filename,
    private array $headers = []
  ) {
    $this->columns = range('A', 'Z');
    $this->handleHeaders();
  }

  private function handleHeaders()
  {
    if (!empty($this->headers)) {
      return $this->headers;
    }
    if (empty($this->data)) {
      throw new Exception('No data to export');
    }
    $this->headers = array_keys($this->data[0] ?? []);
    $this->headers = array_map(
      fn($item) => ucfirst(str_replace(['_', '-'], ' ', $item)),
      $this->headers
    );
    return $this->headers;
  }

  private function build(): Xlsx
  {
    $spreadsheet = new Spreadsheet();
    $workSheet = $spreadsheet->getActiveSheet();

    $this->validateEntries();
    $row = 1;
    $this->insertRow($workSheet, $this->headers, $row);

    foreach ($this->data as $key => $entries) {
      $row += 1;
      $this->insertRow($workSheet, $entries, $row);
    }

    return new Xlsx($spreadsheet);
  }

  function download()
  {
    $xlsx = $this->build();

    $fileName = sanitizeFilename($this->filename);
    $tempFilePath = storage_path("app/public/{$fileName}");
    // Save to a temporary file
    $xlsx->save($tempFilePath);

    return Response::download($tempFilePath, $fileName)->deleteFileAfterSend(
      true
    );
  }

  function insertRow(Worksheet $workSheet, array $rowData, int $row)
  {
    $i = 0;
    foreach ($rowData as $key2 => $value) {
      $column = $this->columns[$i];
      $i++;
      $workSheet->setCellValue("{$column}{$row}", $value);
    }
  }

  function validateEntries()
  {
    if (empty($this->data)) {
      return;
    }
    if (count($this->data[0] ?? 0) !== count($this->headers)) {
      throw new Exception('Invalid headers');
    }
  }
}
