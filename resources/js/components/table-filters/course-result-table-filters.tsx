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

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function CourseResultsTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const [filters, setFilters] = useState(() => ({
    academicSession: params.academicSession ?? currentAcademicSession,
    term: params.term ?? currentTerm,
    classification: params.classification ?? '',
    student: params.student ?? '',
    course: params.course ?? '',
    teacher: params.teacher ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Class">
        <ClassificationSelect
          onChange={(e: any) =>
            setFilters({ ...filters, classification: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Course">
        <CourseSelect
          onChange={(e: any) => setFilters({ ...filters, course: e.value })}
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
    </BaseTableFilter>
  );
}
