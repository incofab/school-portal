import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import { InstitutionUserType } from '@/types/types';
import CourseSelect from '../selectors/course-select';
import StaffSelect from '../selectors/staff-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function CourseTeacherTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    course: params.course ?? '',
    classification: params.classification ?? '',
    teacher: params.teacher ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Subject">
        <CourseSelect
          selectValue={filters.course}
          onChange={(e: any) => setFilters({ ...filters, course: e.value })}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Class">
        <ClassificationSelect
          selectValue={filters.classification}
          onChange={(e: any) =>
            setFilters({ ...filters, classification: e.value })
          }
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Teacher">
        <StaffSelect
          rolesIn={[InstitutionUserType.Teacher]}
          onChange={(e: any) => setFilters({ ...filters, teacher: e.value })}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
