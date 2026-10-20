<?php

use App\Actions\Sheet\LoadSpreadsheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it(
  'loads workbooks whose worksheet relationship uses an absolute package path',
  function () {
    $path = tempnam(sys_get_temp_dir(), 'xlsx-');
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()->setCellValue('A1', 'Imported value');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    $archive = new \ZipArchive();
    expect($archive->open($path))->toBeTrue();
    $relationships = $archive->getFromName('xl/_rels/workbook.xml.rels');
    expect($relationships)->toBeString();
    $relationships = str_replace(
      'Target="worksheets/sheet1.xml"',
      'Target="/xl/worksheets/sheet1.xml"',
      $relationships
    );
    $archive->deleteName('xl/_rels/workbook.xml.rels');
    $archive->addFromString('xl/_rels/workbook.xml.rels', $relationships);
    $archive->close();

    try {
      $loaded = LoadSpreadsheet::fromPath($path);

      expect(
        $loaded
          ->getActiveSheet()
          ->getCell('A1')
          ->getValue()
      )->toBe('Imported value');

      $loaded->disconnectWorksheets();
    } finally {
      unlink($path);
    }
  }
);
