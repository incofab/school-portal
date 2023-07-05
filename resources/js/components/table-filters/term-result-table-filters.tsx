import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import StudentSelect from '../selectors/student-select';
import { TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import useIsStaff from '@/hooks/use-is-staff';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function TermResultsTableFilters({ isOpen, onClose }: Props) {
  const isStaff = useIsStaff();
  const { params } = useQueryString();
  const { currentAcademicSession, currentTerm } = useSharedProps();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    academicSession: params.academicSession ?? currentAcademicSession,
    student: params.student ?? '',
    classification: params.classification ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      {isStaff && (
        <>
          <FilterFormControlBox title="Class">
            <ClassificationSelect
              onChange={(e: any) =>
                setFilters({ ...filters, classification: e.value })
              }
            />
          </FilterFormControlBox>
          <FilterFormControlBox title="Student">
            <StudentSelect
              onChange={(e: any) =>
                setFilters({ ...filters, student: e.value })
              }
            />
          </FilterFormControlBox>
        </>
      )}
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
