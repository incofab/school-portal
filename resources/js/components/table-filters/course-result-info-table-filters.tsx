import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import { TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import CourseSelect from '../selectors/course-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function CourseResultInfoTableFilters({
  isOpen,
  onClose,
}: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    course: params.course ?? '',
    classification: params.classification ?? '',
    academicSession: params.academicSession ?? '',
    term: params.term ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Course">
        <CourseSelect
          onChange={(e: any) => setFilters({ ...filters, course: e.value })}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Class">
        <ClassificationSelect
          onChange={(e: any) =>
            setFilters({ ...filters, classification: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Academic Session">
        <AcademicSessionSelect
          onChange={(e: any) =>
            setFilters({ ...filters, academicSession: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Term">
        <EnumSelect
          enumData={TermType}
          onChange={(e: any) => setFilters({ ...filters, term: e.value })}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
