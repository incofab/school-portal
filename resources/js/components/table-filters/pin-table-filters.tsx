import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import { TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import InstitutionSelect from '../selectors/institution-select';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function PinsTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    academicSession: params.academicSession ?? currentAcademicSessionId,
    institution_id: params.institution ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="School">
        <InstitutionSelect
          onChange={(e: any) =>
            setFilters({ ...filters, institution_id: e.value })
          }
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
