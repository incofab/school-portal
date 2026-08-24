import React, { useState } from 'react';
import { FaqType } from '@/types/types';
import BaseTableFilter from '@/components/table-filters/base-table-filter';
import FilterFormControlBox from '@/components/table-filters/filter-form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import useQueryString from '@/hooks/use-query-string';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function FaqsTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState({ type: params.type ?? '' });

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Type">
        <EnumSelect
          selectValue={filters.type}
          enumData={FaqType}
          value={filters.type}
          refreshKey={filters.type}
          onChange={(option: any) =>
            setFilters({ ...filters, type: option?.value ?? '' })
          }
          isClearable={true}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
