import * as XLSX from 'xlsx';

export type ExcelCellValue =
  | string
  | number
  | boolean
  | Date
  | null
  | undefined;

export interface ExcelCell {
  value?: ExcelCellValue;
  type?: 'string' | 'number' | 'boolean' | 'date';
  rowSpan?: number;
  colSpan?: number;
}

export type ExcelRow = (ExcelCell | ExcelCellValue)[];

export interface ExcelSheetData {
  rows: ExcelRow[];
  merges?: XLSX.Range[];
  columnWidths?: number[];
}

export interface ExportExcelOptions {
  filename: string;
  sheetName?: string;
  table?: HTMLTableElement | null;
  data?: ExcelSheetData;
}

function normalizeFilename(filename: string) {
  return filename.toLowerCase().endsWith('.xlsx')
    ? filename
    : `${filename}.xlsx`;
}

function sanitizeSheetName(sheetName?: string) {
  return (sheetName || 'Sheet 1').replace(/[\\/?*[\]:]/g, '').slice(0, 31);
}

function isCell(value: ExcelCell | ExcelCellValue): value is ExcelCell {
  return (
    typeof value === 'object' &&
    value !== null &&
    !(value instanceof Date) &&
    ('value' in value ||
      'type' in value ||
      'rowSpan' in value ||
      'colSpan' in value)
  );
}

function cellValue(cell: ExcelCell | ExcelCellValue) {
  return isCell(cell) ? cell.value : cell;
}

function cellType(cell: ExcelCell | ExcelCellValue, value: ExcelCellValue) {
  if (isCell(cell) && cell.type) {
    return cell.type;
  }

  if (typeof value === 'number') {
    return 'number';
  }

  if (typeof value === 'boolean') {
    return 'boolean';
  }

  if (value instanceof Date) {
    return 'date';
  }

  return 'string';
}

function sheetFromData(data: ExcelSheetData) {
  const worksheet: XLSX.WorkSheet = {};
  const merges: XLSX.Range[] = [...(data.merges ?? [])];
  const occupied = new Set<string>();
  let maxColumn = 0;

  data.rows.forEach((row, rowIndex) => {
    let columnIndex = 0;

    row.forEach((cell) => {
      while (occupied.has(`${rowIndex}:${columnIndex}`)) {
        columnIndex++;
      }

      const value = cellValue(cell);
      const address = XLSX.utils.encode_cell({ r: rowIndex, c: columnIndex });

      if (value !== undefined && value !== null) {
        const type = cellType(cell, value);
        worksheet[address] = {
          v: value,
          t:
            type === 'number'
              ? 'n'
              : type === 'boolean'
              ? 'b'
              : type === 'date'
              ? 'd'
              : 's',
        };
      }

      const rowSpan = isCell(cell) ? cell.rowSpan ?? 1 : 1;
      const colSpan = isCell(cell) ? cell.colSpan ?? 1 : 1;

      if (rowSpan > 1 || colSpan > 1) {
        merges.push({
          s: { r: rowIndex, c: columnIndex },
          e: { r: rowIndex + rowSpan - 1, c: columnIndex + colSpan - 1 },
        });

        for (let r = rowIndex; r < rowIndex + rowSpan; r++) {
          for (let c = columnIndex; c < columnIndex + colSpan; c++) {
            occupied.add(`${r}:${c}`);
          }
        }
      }

      maxColumn = Math.max(maxColumn, columnIndex + colSpan);
      columnIndex += colSpan;
    });
  });

  worksheet['!ref'] = XLSX.utils.encode_range({
    s: { r: 0, c: 0 },
    e: { r: Math.max(data.rows.length - 1, 0), c: Math.max(maxColumn - 1, 0) },
  });
  worksheet['!merges'] = merges;

  if (data.columnWidths?.length) {
    worksheet['!cols'] = data.columnWidths.map((wch) => ({ wch }));
  }

  return worksheet;
}

export function exportToExcel({
  filename,
  sheetName,
  table,
  data,
}: ExportExcelOptions) {
  const worksheet = data
    ? sheetFromData(data)
    : table
    ? XLSX.utils.table_to_sheet(table, { raw: true })
    : null;

  if (!worksheet) {
    return;
  }

  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(
    workbook,
    worksheet,
    sanitizeSheetName(sheetName)
  );
  XLSX.writeFile(workbook, normalizeFilename(filename), { bookType: 'xlsx' });
}
