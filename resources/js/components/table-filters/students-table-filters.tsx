import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import EnumSelect from '../dropdown-select/enum-select';
import { InstitutionUserType } from '@/types/types';
import DateRangeFilter, { getDateRangeFilterParams } from './date-range-filter';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function StudentsTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    classification: params.classification ?? '',
    studentRole: params.studentRole ?? '',
    ...getDateRangeFilterParams(params, 'created_at'),
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Class">
        <ClassificationSelect
          selectValue={filters.classification}
          onChange={(e: any) =>
            setFilters({ ...filters, classification: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Role">
        <EnumSelect
          enumData={InstitutionUserType}
          additionalEnumData={{ All: 'all' }}
          allowedEnum={[
            InstitutionUserType.Student,
            InstitutionUserType.Alumni,
            'all',
          ]}
          selectValue={filters.studentRole}
          isClearable={true}
          onChange={(e: any) =>
            setFilters({ ...filters, studentRole: e.value })
          }
        />
      </FilterFormControlBox>
      <DateRangeFilter
        label="Registration Date"
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
