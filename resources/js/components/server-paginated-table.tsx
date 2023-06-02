import React, { useEffect, useMemo, useState } from 'react';
import { Div } from '@/components/semantic';
import {
  Box,
  Divider,
  HStack,
  Icon,
  IconButton,
  Spacer,
  Table,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
} from '@chakra-ui/react';
import {
  ArrowLeftCircleIcon,
  ArrowRightCircleIcon,
  ArrowDownIcon,
  ArrowUpIcon,
} from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import { PaginationResponse } from '@/types/types';
import objectGet from 'lodash/get';
import FieldSearchForm from './field-search-form';
import searchContent from '@/util/search-content';
import FilterButton from './filter-button';

export interface ServerPaginatedTableHeader<T = any> {
  label: string;
  value?: string;
  render?(row: T): JSX.Element | string | null;
  sortKey?: string;
}

function isHeaderSortable(header: ServerPaginatedTableHeader) {
  return Boolean(header.sortKey);
}

interface Props<T> {
  data: T[];
  headers: ServerPaginatedTableHeader<T>[];
  keyExtractor(row: T): string | number;
  paginator: PaginationResponse<any>;
  scroll?: boolean;
  hideSearchField?: boolean;
  validFilters?: string[];
  onFilterButtonClick?: () => void;
}

export default function ServerPaginatedTable<T>({
  data,
  headers,
  keyExtractor,
  paginator,
  scroll,
  hideSearchField,
  validFilters,
  onFilterButtonClick,
}: Props<T>) {
  const [content, setContent] = useState<T[]>(data);
  useEffect(() => setContent(data), [data]);

  const params = useMemo(() => {
    const url = new URL(window.location.href);
    const params: { [key: string]: string } = {};
    url.searchParams.forEach((val, key) => {
      params[key] = val;
    });
    return params;
  }, [window.location.href]);

  function updateQueryAndNavigate(newParams: { [key: string]: string }) {
    const url = new URL(window.location.href);
    for (const key in newParams) {
      url.searchParams.set(key, newParams[key]);
    }
    Inertia.visit(url.toString());
  }

  function getNewSortDir(header: ServerPaginatedTableHeader) {
    // column already sorting, reverse the order
    if (params.sortKey === header.sortKey) {
      return params.sortDir === 'asc' ? 'desc' : 'asc';
    }
    return 'asc';
  }

  function sortHeader(header: ServerPaginatedTableHeader) {
    if (!isHeaderSortable(header)) {
      return;
    }
    updateQueryAndNavigate({
      sortKey: header.sortKey!,
      sortDir: getNewSortDir(header),
    });
  }

  function setPerPage(amount: number) {
    updateQueryAndNavigate({
      perPage: `${amount}`,
    });
  }

  function goToPrevPage() {
    updateQueryAndNavigate({ page: `${Number(params.page || 1) - 1}` });
  }

  function goToNextPage() {
    updateQueryAndNavigate({ page: `${Number(params.page || 1) + 1}` });
  }

  function getNestedProperty(row: T, path: string): any {
    return objectGet(row, path, '');
  }

  return (
    <Div>
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
      <Div overflow={scroll ? 'auto' : undefined}>
        <Table size={'sm'}>
          <Thead>
            <Tr>
              {headers.map((header, i) => (
                <Th
                  key={i}
                  onClick={() => sortHeader(header)}
                  cursor={isHeaderSortable(header) ? 'pointer' : undefined}
                >
                  <HStack spacing={2}>
                    <Text fontWeight={400}>{header.label}</Text>
                    {header.sortKey && header.sortKey === params.sortKey ? (
                      params.sortDir === 'desc' ? (
                        <Icon as={ArrowDownIcon} />
                      ) : (
                        <Icon as={ArrowUpIcon} />
                      )
                    ) : null}
                  </HStack>
                </Th>
              ))}
            </Tr>
          </Thead>
          <Tbody>
            {content.map((row) => (
              <Tr key={keyExtractor(row)}>
                {headers.map((header, i) => (
                  <Td key={i}>
                    {header.render
                      ? header.render(row)
                      : header.value
                      ? getNestedProperty(row, header.value)
                      : null}
                  </Td>
                ))}
              </Tr>
            ))}
          </Tbody>
        </Table>
      </Div>
      <Div display={'flex'} justifyContent={'end'} mt={4}>
        <HStack spacing={8}>
          {/* <HStack spacing={2}>
            <Text fontSize={'sm'}>Per Page </Text>
            <HStack
              spacing={2}
              h={6}
              divider={<Divider orientation={'vertical'} />}
            >
              {[10, 25, 50].map((amount) => (
                <Box
                  key={amount}
                  as={'button'}
                  onClick={() => setPerPage(amount)}
                  bg={'transparent'}
                  fontWeight={amount === paginator.per_page ? 'bold' : 'normal'}
                >
                  {amount}
                </Box>
              ))}
            </HStack>
          </HStack> */}
          <HStack spacing={2}>
            {paginator.prev_page_url && (
              <IconButton
                aria-label={'Prev Page'}
                icon={<Icon as={ArrowLeftCircleIcon} />}
                size={'md'}
                variant={'ghost'}
                onClick={() => goToPrevPage()}
              />
            )}
            <Text fontSize={'sm'}>
              Showing {paginator.from || 0} to {paginator.to || 0} of{' '}
              {paginator.total || 0}
            </Text>
            {paginator.next_page_url && (
              <IconButton
                aria-label={'Next Page'}
                icon={<Icon as={ArrowRightCircleIcon} />}
                size={'md'}
                variant={'ghost'}
                onClick={() => goToNextPage()}
              />
            )}
          </HStack>
        </HStack>
      </Div>
    </Div>
  );
}
