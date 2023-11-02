import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import StudentSelect from '../selectors/student-select';
import StaffSelect from '../selectors/staff-select';
import { InstitutionUserType, TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function StudentClassMovementTableFilters({
  isOpen,
  onClose,
}: Props) {
  const { params } = useQueryString();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const [filters, setFilters] = useState(() => ({
    academicSession: params.academicSession ?? currentAcademicSessionId,
    term: params.term ?? currentTerm,
    student: params.student ?? '',
    sourceClass: params.sourceClass ?? '',
    destinationClass: params.destinationClass ?? '',
    user: params.user ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Source Class">
        <ClassificationSelect
          selectValue={filters.sourceClass}
          onChange={(e: any) =>
            setFilters({ ...filters, sourceClass: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Destination Class">
        <ClassificationSelect
          selectValue={filters.destinationClass}
          onChange={(e: any) =>
            setFilters({ ...filters, destinationClass: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Student">
        <StudentSelect
          onChange={(e: any) => setFilters({ ...filters, student: e.value })}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Staff">
        <StaffSelect
          rolesIn={[InstitutionUserType.Teacher, InstitutionUserType.Admin]}
          onChange={(e: any) => setFilters({ ...filters, user: e.value })}
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
    </BaseTableFilter>
  );
}
