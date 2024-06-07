import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import StaffSelect from '../selectors/staff-select';
import { InstitutionUserType, TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import FeeSelect from '../selectors/fee-select';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function FeePaymentTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    academicSession: params.academicSession ?? currentAcademicSessionId,
    fee: params.fee ?? '',
    user: params.user ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Fee">
        <FeeSelect
          selectValue={filters.fee}
          onChange={(e: any) => setFilters({ ...filters, fee: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Student">
        <StaffSelect
          rolesIn={[InstitutionUserType.Student]}
          onChange={(e: any) => setFilters({ ...filters, user: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Academic Session">
        <AcademicSessionSelect
          selectValue={filters.academicSession}
          onChange={(e: any) =>
            setFilters({ ...filters, academicSession: e?.value })
          }
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Term">
        <EnumSelect
          selectValue={filters.term}
          enumData={TermType}
          onChange={(e: any) => setFilters({ ...filters, term: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
