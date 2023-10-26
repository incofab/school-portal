import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import StudentSelect from '../selectors/student-select';
import CourseSelect from '../selectors/course-select';
import StaffSelect from '../selectors/staff-select';
import { InstitutionUserType, TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import useSharedProps from '@/hooks/use-shared-props';
import SelectMidTerm from './mid-term-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function CourseResultsTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentAcademicSessionId, currentTerm, usesMidTermResult } =
    useSharedProps();
  const [filters, setFilters] = useState(() => ({
    academicSession: params.academicSession ?? currentAcademicSessionId,
    term: params.term ?? currentTerm,
    classification: params.classification ?? '',
    student: params.student ?? '',
    course: params.course ?? '',
    teacher: params.teacher ?? '',
    forMidTerm: params.forMidTerm,
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
      <FilterFormControlBox title="Subject">
        <CourseSelect
          selectValue={filters.course}
          onChange={(e: any) => setFilters({ ...filters, course: e.value })}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Academic Session">
        <AcademicSessionSelect
          selectValue={filters.academicSession}
          onChange={(e: any) =>
            setFilters({ ...filters, academicSession: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Term">
        <EnumSelect
          selectValue={filters.term}
          enumData={TermType}
          onChange={(e: any) => setFilters({ ...filters, term: e.value })}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Student">
        <StudentSelect
          onChange={(e: any) => setFilters({ ...filters, student: e.value })}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Teacher">
        <StaffSelect
          rolesIn={[InstitutionUserType.Teacher]}
          onChange={(e: any) => setFilters({ ...filters, teacher: e.value })}
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
