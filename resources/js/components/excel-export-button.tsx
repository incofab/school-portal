import React from 'react';
import { Button, ButtonProps, Icon } from '@chakra-ui/react';
import { ArrowDownTrayIcon } from '@heroicons/react/24/outline';
import { ExcelSheetData, exportToExcel } from '@/util/excel-export';

interface ExcelExportButtonProps extends ButtonProps {
  filename: string;
  sheetName?: string;
  table?: HTMLTableElement | null;
  tableSelector?: string;
  contentId?: string;
  data?: ExcelSheetData;
}

function combineTables(tables: HTMLTableElement[]) {
  const combinedTable = document.createElement('table');

  tables.forEach((table, tableIndex) => {
    if (tableIndex > 0) {
      const spacerRow = document.createElement('tr');
      spacerRow.appendChild(document.createElement('td'));
      combinedTable.appendChild(spacerRow);
    }

    Array.from(table.rows).forEach((row) => {
      combinedTable.appendChild(row.cloneNode(true));
    });
  });

  return combinedTable;
}

export default function ExcelExportButton({
  filename,
  sheetName,
  table,
  tableSelector,
  contentId,
  data,
  children,
  ...props
}: ExcelExportButtonProps) {
  function resolveTable() {
    if (table) {
      return table;
    }

    if (!tableSelector && !contentId) {
      return null;
    }

    const root = contentId ? document.getElementById(contentId) : document;
    const tables = Array.from(
      root?.querySelectorAll<HTMLTableElement>(tableSelector ?? 'table') ?? []
    );

    if (tables.length === 0) {
      return null;
    }

    return tables.length === 1 ? tables[0] : combineTables(tables);
  }

  return (
    <Button
      leftIcon={<Icon as={ArrowDownTrayIcon} />}
      size="sm"
      variant="outline"
      onClick={() =>
        exportToExcel({ filename, sheetName, table: resolveTable(), data })
      }
      {...props}
    >
      {children ?? 'Export to Excel'}
    </Button>
  );
}
