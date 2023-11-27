import React, { useEffect, useState } from 'react';
import { Div } from '@/components/semantic';
import {
  BoxProps,
  Divider,
  HStack,
  Spacer,
  Table,
  TableProps,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
} from '@chakra-ui/react';
import objectGet from 'lodash/get';
import searchContent from '@/util/search-content';
import FieldSearchForm from './field-search-form';
import FilterButton from './filter-button';

export interface TableHeader<T = any> {
  label: string;
  value?: string;
  render?(row: T): JSX.Element | string | null;
}

interface Props<T> {
  data: T[];
  headers: TableHeader<T>[];
  keyExtractor(row: T): string | number;
  rowPropsExtractor?(row: T): BoxProps;
  scroll?: boolean;
  hideSearchField?: boolean;
  validFilters?: string[];
  onFilterButtonClick?: () => void;
  //styles
  tableProps?: TableProps;
}

export default function DataTable<T>({
  data,
  headers,
  keyExtractor,
  rowPropsExtractor,
  scroll,
  hideSearchField,
  validFilters,
  onFilterButtonClick,
  tableProps,
}: Props<T>) {
  function getNestedProperty(row: T, path: string): any {
    return objectGet(row, path, '');
  }
  const [content, setContent] = useState<T[]>(data);
  useEffect(() => setContent(data), [data]);

  return (
    <Div overflow={scroll ? 'auto' : undefined}>
      <HStack>
        {!hideSearchField && (
          <FieldSearchForm
            onChange={(value: string) => setContent(searchContent(data, value))}
          />
        )}
        <Spacer />
        {validFilters && (
          <FilterButton
            validFilters={validFilters}
            onClick={onFilterButtonClick}
          />
        )}
      </HStack>
      <Divider my={2} />
      <Table size={'sm'} {...tableProps}>
        <Thead>
          <Tr>
            {headers.map((header, i) => (
              <Th key={i}>
                <HStack spacing={2}>
                  <Text fontWeight={400}>{header.label}</Text>
                </HStack>
              </Th>
            ))}
          </Tr>
        </Thead>
        <Tbody>
          {content.length > 0 ? (
            content.map((row) => (
              <Tr
                key={keyExtractor(row)}
                {...(rowPropsExtractor ? rowPropsExtractor(row) : null)}
              >
                {headers.map((header: TableHeader<T>, i: number) => (
                  <Td key={i}>
                    {header.render
                      ? header.render(row)
                      : header.value
                      ? getNestedProperty(row, header.value)
                      : null}
                  </Td>
                ))}
              </Tr>
            ))
          ) : (
            <Tr>
              <Td colSpan={headers.length}>
                <Text textAlign={'center'} py={2} mt={2}>
                  No results found
                </Text>
              </Td>
            </Tr>
          )}
        </Tbody>
      </Table>
    </Div>
  );
}
