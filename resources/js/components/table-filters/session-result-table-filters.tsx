import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import StudentSelect from '../selectors/student-select';
import AcademicSessionSelect from '../selectors/academic-session-select';
import useIsStaff from '@/hooks/use-is-staff';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function SessionResultsTableFilters({ isOpen, onClose }: Props) {
  const isStaff = useIsStaff();
  const { params } = useQueryString();
  const { currentAcademicSession } = useSharedProps();

  const [filters, setFilters] = useState(() => ({
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
              selectValue={filters.classification}
              onChange={(e: any) =>
                setFilters({ ...filters, classification: e.value })
              }
            />
          </FilterFormControlBox>
          <FilterFormControlBox title="Student">
            <StudentSelect
              value={filters.student}
              onChange={(e: any) =>
                setFilters({ ...filters, student: e.value })
              }
            />
          </FilterFormControlBox>
        </>
      )}
      <FilterFormControlBox title="Academic Session">
        <AcademicSessionSelect
          selectValue={filters.academicSession}
          onChange={(e: any) =>
            setFilters({ ...filters, academicSession: e.value })
          }
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
