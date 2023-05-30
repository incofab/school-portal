import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import EnumSelect from '../dropdown-select/enum-select';
import { InstitutionUserType } from '@/types/types';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function StaffTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    role: params.role ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Role">
        <EnumSelect
          enumData={InstitutionUserType}
          onChange={(e: any) =>
            setFilters({ ...filters, role: e.currentTarget.value })
          }
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
