import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import CourseSelect from '../selectors/course-select';
import ClassificationGroupSelect from '../selectors/classification-group-select';
import useIsStaff from '@/hooks/use-is-staff';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function TopicTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const isStaff = useIsStaff();
  const [filters, setFilters] = useState(() => ({
    classificationGroup: params.classificationGroup ?? '',
    course: params.course ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      {isStaff && (
        <>
          <FilterFormControlBox title="Class Group">
            <ClassificationGroupSelect
              selectValue={filters.classificationGroup}
              onChange={(e: any) =>
                setFilters({ ...filters, classificationGroup: e.value })
              }
            />
          </FilterFormControlBox>
        </>
      )}

      <FilterFormControlBox title="Subject">
        <CourseSelect
          selectValue={filters.course}
          onChange={(e: any) => setFilters({ ...filters, course: e.value })}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
