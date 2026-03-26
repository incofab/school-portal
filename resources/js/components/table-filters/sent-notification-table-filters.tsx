import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import { Input } from '@chakra-ui/react';
import DateRangeFilter, { getDateRangeFilterParams } from './date-range-filter';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function SentNotificationTableFilters({
  isOpen,
  onClose,
}: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    search: params.search ?? '',
    type: params.type ?? '',
    // fromDate: params.fromDate ?? '',
    // toDate: params.toDate ?? '',
    ...getDateRangeFilterParams(params, 'created_at'),
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Search">
        <Input
          value={filters.search}
          onChange={(e) => setFilters({ ...filters, search: e.target.value })}
          placeholder="Search title or message"
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Type">
        <Input
          value={filters.type}
          onChange={(e) => setFilters({ ...filters, type: e.target.value })}
          placeholder="e.g class-update"
        />
      </FilterFormControlBox>
      {/* <FilterFormControlBox title="From Date">
        <Input
          type="date"
          value={filters.fromDate}
          onChange={(e) =>
            setFilters({ ...filters, fromDate: e.currentTarget.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="To Date">
        <Input
          type="date"
          value={filters.toDate}
          onChange={(e) => setFilters({ ...filters, toDate: e.currentTarget.value })}
        />
      </FilterFormControlBox> */}
      <DateRangeFilter
        label="Created Date"
        filterKey="created_at"
        filters={filters}
        onChange={(dateRange) =>
          setFilters({
            ...filters,
            ...dateRange,
          })
        }
        // onChange={(key, value) => setFilters({ ...filters, [key]: value })}
      />
    </BaseTableFilter>
  );
}
