import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import { TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import CourseSelect from '../selectors/course-select';
import useSharedProps from '@/hooks/use-shared-props';
import SelectMidTerm from './mid-term-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function CourseResultInfoTableFilters({
  isOpen,
  onClose,
}: Props) {
  const { params } = useQueryString();
  const { currentAcademicSession, currentTerm, usesMidTermResult } =
    useSharedProps();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    academicSession: params.academicSession ?? currentAcademicSession,
    course: params.course ?? '',
    classification: params.classification ?? '',
    forMidTerm: params.forMidTerm ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Subject">
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
      {usesMidTermResult && (
        <FilterFormControlBox title="forMidTerm">
          <SelectMidTerm
            value={String(filters.forMidTerm) ?? undefined}
            onChange={(e) => setFilters({ ...filters, forMidTerm: e })}
            children={null}
          />
        </FilterFormControlBox>
      )}
    </BaseTableFilter>
  );
}
