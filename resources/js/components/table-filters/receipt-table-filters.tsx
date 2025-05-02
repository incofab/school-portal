import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import { TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import useSharedProps from '@/hooks/use-shared-props';
import StudentSelect from '../selectors/student-select';
import FeeSelect from '../selectors/fee-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function ReceiptTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    academicSession: params.academicSession ?? currentAcademicSessionId,
    classification: params.classification ?? '',
    studentClass: params.studentClass ?? '',
    user: params.user ?? '',
    fee: params.fee ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Academic Session">
        <AcademicSessionSelect
          selectValue={filters.academicSession}
          isClearable={true}
          onChange={(e: any) =>
            setFilters({ ...filters, academicSession: e?.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Term">
        <EnumSelect
          selectValue={filters.term}
          enumData={TermType}
          isClearable={true}
          onChange={(e: any) => setFilters({ ...filters, term: e?.value })}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Student">
        <StudentSelect
          value={filters.user}
          isMulti={false}
          isClearable={true}
          valueKey={'user_id'}
          onChange={(e: any) => setFilters({ ...filters, user: e?.value })}
          classification={filters.classification}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Class">
        <ClassificationSelect
          selectValue={filters.studentClass}
          isClearable={true}
          onChange={(e: any) =>
            setFilters({ ...filters, studentClass: e?.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Fee">
        <FeeSelect
          selectValue={filters.fee}
          onChange={(e: any) => setFilters({ ...filters, fee: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
